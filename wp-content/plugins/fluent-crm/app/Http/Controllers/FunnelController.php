<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Reporting;
use FluentCrm\App\Services\Sanitize;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Validator\ValidationException;

/**
 *  FunnelController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class FunnelController extends Controller
{
    public function funnels(Request $request)
    {
        $this->maybeMigrateDB();

        $orderBy = $request->getSafe('sort_by', 'id', 'sanitize_sql_orderby');
        $orderType = $request->getSafe('sort_type', 'DESC', 'sanitize_sql_orderby');

        $funnelQuery = Funnel::orderBy($orderBy, $orderType);
        if ($search = $request->getSafe('search')) {
            $funnelQuery->where('title', 'LIKE', '%%' . $search . '%%');
        }
        $funnels = $funnelQuery->paginate();
        $with = $this->request->get('with', []);

        foreach ($funnels as $funnel) {
            $funnel->subscribers_count = $funnel->getSubscribersCount();
        }

        $data = [
            'funnels' => $funnels
        ];

        if (in_array('triggers', $with)) {
            $data['triggers'] = $this->getTriggers();
        }

        return $this->sendSuccess($data);
    }

    public function getFunnel(Request $request, $funnelId)
    {
        $with = $request->get('with', []);
        $funnel = Funnel::findOrFail($funnelId);

        if (defined('MEPR_PLUGIN_NAME')) {
            // Maybe trigger name changed
            $migrationMaps = [
                'recurring-transaction-expired' => 'mepr-event-transaction-expired'
            ];

            if (isset($migrationMaps[$funnel->trigger_name])) {
                $funnel->trigger_name = $migrationMaps[$funnel->trigger_name];
                $funnel->save();
            }
        }

        $triggers = $this->getTriggers();
        if (isset($triggers[$funnel->trigger_name])) {
            $funnel->trigger = $triggers[$funnel->trigger_name];
        }

        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        $funnel->description = $funnel->getMeta('description');

        $data = [
            'funnel' => $funnel
        ];

        if (in_array('blocks', $with)) {
            $data['blocks'] = $this->getBlocks($funnel);
        }

        if (in_array('block_fields', $with)) {
            $data['block_fields'] = $this->getBlockFields($funnel);
            $data['composer_context_codes'] = apply_filters('fluent_crm_funnel_context_smart_codes', [], $funnel->trigger_name, $funnel);
        }

        if (in_array('funnel_sequences', $with)) {
            FunnelHelper::maybeMigrateConditions($funnel->id);
            $data['funnel_sequences'] = $this->getFunnelSequences($funnel, true);
        }

        return $this->sendSuccess($data);
    }

    public function create(Request $request)
    {
        try {
            $funnel = $this->validate($request->get('funnel'), [
                'title'        => 'required',
                'trigger_name' => 'required'
            ]);

            $description = sanitize_textarea_field(Arr::get($funnel, 'description'));

            $funnelData = Arr::only($funnel, ['title', 'trigger_name']);
            $funnelData['status'] = 'draft';
            $funnelData['settings'] = [];
            $funnelData['conditions'] = [];
            $funnelData['created_by'] = get_current_user_id();

            $funnelData = Sanitize::funnel($funnelData);
            $funnel = Funnel::create($funnelData);

            if ($description) {
                $funnel->updateMeta('description', $description);
            }

            return $this->sendSuccess([
                'funnel'  => $funnel,
                'message' => __('Funnel has been created. Please configure now', 'fluent-crm')
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }
    }

    public function delete(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $funnel->deleteMeta('description');

        Funnel::where('id', $funnelId)->delete();
        FunnelSequence::where('funnel_id', $funnelId)->delete();
        FunnelSubscriber::where('funnel_id', $funnelId)->delete();

        return $this->sendSuccess([
            'message' => __('Funnel has been deleted', 'fluent-crm')
        ]);
    }

    public function getTriggersRest()
    {
        return [
            'triggers' => $this->getTriggers()
        ];
    }

    public function changeTrigger(Request $request, $funnelId)
    {
        $data = $request->only(['title', 'trigger_name']);

        $this->validate($data, [
            'trigger_name' => 'required',
            'title'        => 'required'
        ]);

        $funnel = Funnel::findOrFail($funnelId);

        if ($funnel->trigger_name == $data['trigger_name']) {
            return $this->sendError([
                'message' => __('Trigger name is same', 'fluent-crm')
            ]);
        }

        $funnel->trigger_name = sanitize_text_field($data['trigger_name']);
        $funnel->title = sanitize_text_field($data['title']);

        $funnel->settings = [];
        $funnel->conditions = [];
        $funnel->save();

        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        return $this->sendSuccess([
            'message' => __('Funnel Trigger has been successfully updated', 'fluent-crm'),
            'funnel'  => $funnel
        ]);

    }

    private function getTriggers()
    {
        return apply_filters('fluentcrm_funnel_triggers', []);
    }

    private function getBlocks($funnel)
    {
        return apply_filters('fluentcrm_funnel_blocks', [], $funnel);
    }

    private function getBlockFields($funnel)
    {
        return apply_filters('fluentcrm_funnel_block_fields', [], $funnel);
    }

    public function getFunnelSequences($funnel, $isFiltered = false)
    {
        $sequences = FunnelHelper::getFunnelSequences($funnel, $isFiltered);
        $formattedSequences = [];
        $childs = [];

        foreach ($sequences as $sequence) {
            if ($sequence['type'] == 'conditional') {
                $sequence['children'] = [
                    'yes' => [],
                    'no'  => []
                ];
            } else if ($sequence['type'] == 'benchmark') {
                //  @todo: we may delete this mid 2023
                if (empty($sequence['settings']['can_enter'])) {
                    $sequence['settings']['can_enter'] = 'yes';
                }
            }

            if ($parentId = Arr::get($sequence, 'parent_id')) {
                if (!isset($childs[$parentId]['yes'])) {
                    $childs[$parentId]['yes'] = [];
                }
                if (!isset($childs[$parentId]['no'])) {
                    $childs[$parentId]['no'] = [];
                }
                $childs[$parentId][$sequence['condition_type']][] = $sequence;
            } else {
                $formattedSequences[$sequence['id']] = $sequence;
            }
        }

        if ($childs) {
            foreach ($childs as $sequenceId => $children) {
                if (isset($formattedSequences[$sequenceId])) {
                    $formattedSequences[$sequenceId]['children'] = $children;
                }
            }
        }

        return array_values($formattedSequences);
    }

    public function saveSequencesFallback(Request $request)
    {
        $funnelId = $request->get('funnel_id');
        return $this->saveSequences($request, $funnelId);
    }

    public function saveSequences(Request $request, $funnelId)
    {
        $data = $request->all();

        $funnel = FunnelHelper::saveFunnelSequence($funnelId, $data);

        return $this->sendSuccess([
            'sequences' => $this->getFunnelSequences($funnel, true),
            'message'   => __('Sequence successfully updated', 'fluent-crm')
        ]);
    }

    public function getSubscribers(Request $request, $funnelId)
    {

        $funnel = Funnel::findOrFail($funnelId);

        $search = $request->getSafe('search');
        $status = $request->getSafe('status', '');

        $funnelSubscribersQuery = FunnelSubscriber::with([
            'subscriber',
            'last_sequence',
            'next_sequence_item',
            'metrics' => function ($query) use ($funnelId) {
                $query->where('funnel_id', $funnelId);
            }
        ])
            ->orderBy('id', 'DESC')
            ->where('funnel_id', $funnelId);

        if ($search) {
            $funnelSubscribersQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        $sequenceId = (int)$request->get('sequence_id');
        if ($sequenceId) {
            $funnelSubscribersQuery->whereHas('metrics', function ($q) use ($sequenceId) {
                $q->where('sequence_id', $sequenceId);
            });
        }

        if ($status) {
            $funnelSubscribersQuery->where('status', $status);
        }

        $funnelSubscribers = $funnelSubscribersQuery->paginate();

        $data = [
            'funnel_subscribers' => $funnelSubscribers
        ];

        $with = $request->get('with', []);

        if (in_array('funnel', $with)) {
            $data['funnel'] = $funnel;
        }

        if (in_array('sequences', $with)) {
            $sequences = FunnelSequence::where('funnel_id', $funnelId)
                ->orderBy('sequence', 'ASC')
                ->get();
            $formattedSequences = [];
            foreach ($sequences as $sequence) {
                $formattedSequences[] = $sequence;
            }
            $data['sequences'] = $formattedSequences;
        }

        return $this->sendSuccess($data);
    }

    public function getAllActivities(Request $request)
    {
        $search = $request->getSafe('search');
        $status = $request->getSafe('status', '');

        $funnelSubscribersQuery = FunnelSubscriber::with([
            'subscriber',
            'last_sequence',
            'next_sequence_item',
            'funnel.actions' => function ($query) {
                $query->orderBy('sequence', 'ASC');
            }
        ])
            ->orderBy('id', 'DESC');

        if ($search) {
            $funnelSubscribersQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        if ($status) {
            $funnelSubscribersQuery->where('status', $status);
        }

        $funnelSubscribers = $funnelSubscribersQuery->paginate();

        foreach ($funnelSubscribers as $funnelSubscriber) {
            $funnelSubscriber->metrics = FunnelMetric::where('funnel_id', $funnelSubscriber->funnel_id)
                ->where('subscriber_id', $funnelSubscriber->subscriber_id)
                ->get();
        }

        return [
            'activities' => $funnelSubscribers
        ];
    }

    public function removeBulkSubscribers(Request $request)
    {
        $funnel_subscriber_ids = $request->get('funnel_subscriber_ids', []);

        $funnel_subscriber_ids = array_map('intval', $funnel_subscriber_ids);

        if (!$funnel_subscriber_ids) {
            return $this->sendError([
                'message' => __('Please provide funnel subscriber IDs', 'fluent-crm')
            ]);
        }

        $items = FunnelSubscriber::whereIn('id', $funnel_subscriber_ids)->get();

        foreach ($items as $item) {
            FunnelMetric::where('funnel_id', $item->funnel_id)
                ->where('subscriber_id', $item->subscriber_id)
                ->delete();
        }

        FunnelSubscriber::whereIn('id', $funnel_subscriber_ids)->delete();

        return [
            'message' => __('Selected subscribers has been removed from this automation funnels', 'fluent-crm')
        ];
    }

    public function report(Request $request, Reporting $reporting, $funnelId)
    {
        return [
            'stats' => $reporting->funnelStat($funnelId)
        ];
    }

    public function updateFunnelProperty(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $newStatus = $request->getSafe('status');

        if ($funnel->status == $newStatus) {
            return $this->sendError([
                'message' => 'Funnel already have the same status'
            ]);
        }

        $funnel->status = $newStatus;
        $funnel->save();

        return $this->sendSuccess([
            'message' => __(sprintf('Status has been updated to %s', $newStatus), 'fluent-crm')
        ]);
    }

    public function handleBulkAction(Request $request)
    {
        $actionName = $request->getSafe('action_name', '');

        $funnelIds = $request->getSafe('funnel_ids', [], 'intval');

        $funnelIds = array_unique(array_filter($funnelIds));

        if (!$funnelIds) {
            return $this->sendError([
                'message' => __('Please provide funnel IDs', 'fluent-crm')
            ]);
        }

        if ($actionName == 'change_funnel_status') {
            $newStatus = sanitize_text_field($request->get('status', ''));
            if (!$newStatus) {
                return $this->sendError([
                    'message' => __('Please select status', 'fluent-crm')
                ]);
            }

            $funnels = Funnel::whereIn('id', $funnelIds)->get();

            foreach ($funnels as $funnel) {
                $oldStatus = $funnel->status;
                if ($oldStatus != $newStatus) {
                    $funnel->status = $newStatus;
                    $funnel->save();
                }
            }

            (new FunnelHandler())->resetFunnelIndexes();

            return $this->sendSuccess([
                'message' => __('Status has been changed for the selected funnels', 'fluent-crm')
            ]);
        }

        if ($actionName == 'delete_funnels') {

            $funnels = Funnel::whereIn('id', $funnelIds)->get();

            foreach ($funnels as $funnel) {
                $sequences = FunnelSequence::whereIn('funnel_id', $funnelIds)->get();
                foreach ($sequences as $deletingSequence) {
                    do_action('fluentcrm_funnel_sequence_deleting_' . $deletingSequence->action_name, $deletingSequence, $funnel);
                    $deletingSequence->delete();
                }
                FunnelSubscriber::whereIn('funnel_id', $funnelIds)->delete();
                $funnel->delete();
            }

            (new FunnelHandler())->resetFunnelIndexes();

            return $this->sendSuccess([
                'message' => __('Selected Funnels has been deleted permanently', 'fluent-crm'),
            ]);

        }

        return $this->sendError([
            'message' => __('invalid bulk action', 'fluent-crm')
        ]);
    }

    public function cloneFunnel(Request $request, $funnelId)
    {
        $oldFunnel = Funnel::findOrFail($funnelId);

        $newFunnelData = [
            'title'        => __('[Copy] ', 'fluent-crm') . $oldFunnel->title,
            'trigger_name' => $oldFunnel->trigger_name,
            'status'       => 'draft',
            'conditions'   => $oldFunnel->conditions,
            'settings'     => $oldFunnel->settings,
            'created_by'   => get_current_user_id()
        ];

        $funnel = Funnel::create($newFunnelData);

        $sequences = FunnelHelper::getFunnelSequences($oldFunnel, true);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        $childs = [];
        $oldNewMaps = [];

        foreach ($sequences as $index => $sequence) {
            $oldId = $sequence['id'];
            unset($sequence['id']);
            unset($sequence['created_at']);
            unset($sequence['updated_at']);

            // it's creatable
            $sequence['funnel_id'] = $funnel->id;
            $sequence['status'] = 'published';
            $sequence['conditions'] = [];
            $sequence['sequence'] = $index + 1;
            $sequence['c_delay'] = $cDelay;
            $sequence['delay'] = $delay;
            $delay = 0;

            $actionName = $sequence['action_name'];

            if ($actionName == 'fluentcrm_wait_times') {
                $delay = FunnelHelper::getDelayInSecond($sequence['settings']);
                $cDelay += $delay;
            }

            $sequence = apply_filters('fluentcrm_funnel_sequence_saving_' . $sequence['action_name'], $sequence, $funnel);
            if (Arr::get($sequence, 'type') == 'benchmark') {
                $delay = $sequence['delay'];
            }

            $sequence['created_by'] = get_current_user_id();

            $parentId = Arr::get($sequence, 'parent_id');

            if ($parentId) {
                $childs[$parentId][] = $sequence;
            } else {
                $createdSequence = FunnelSequence::create($sequence);
                $sequenceIds[] = $createdSequence->id;
                $oldNewMaps[$oldId] = $createdSequence->id;
            }
        }

        if ($childs) {
            foreach ($childs as $oldParentId => $childBlocks) {
                foreach ($childBlocks as $childBlock) {
                    $newParentId = Arr::get($oldNewMaps, $oldParentId);
                    if ($newParentId) {
                        $childBlock['parent_id'] = $newParentId;
                        $createdSequence = FunnelSequence::create($childBlock);
                        $sequenceIds[] = $createdSequence->id;
                    }
                }
            }
        }

        FunnelHelper::maybeMigrateConditions($funnel->id);
        (new FunnelHandler())->resetFunnelIndexes();

        return [
            'message' => __('Funnel has been successfully cloned', 'fluent-crm'),
            'funnel'  => $funnel
        ];
    }

    public function importFunnel(Request $request)
    {
        $funnelArray = $request->get('funnel');
        $sequences = $request->getJson('sequences');

        $funnelArray = Sanitize::funnel($funnelArray);

        $newFunnelData = [
            'title'        => $funnelArray['title'],
            'trigger_name' => $funnelArray['trigger_name'],
            'status'       => 'draft',
            'conditions'   => Arr::get($funnelArray, 'conditions', []),
            'settings'     => $funnelArray['settings'],
            'created_by'   => get_current_user_id()
        ];

        $funnel = Funnel::create($newFunnelData);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        $childs = [];
        $oldNewMaps = [];


        foreach ($sequences as $index => $sequence) {
            $oldId = $sequence['id'];
            unset($sequence['id']);
            unset($sequence['created_at']);
            unset($sequence['updated_at']);
            // it's creatable
            $sequence['funnel_id'] = $funnel->id;
            $sequence['status'] = 'published';
            $sequence['conditions'] = [];
            $sequence['sequence'] = $index + 1;
            $sequence['c_delay'] = $cDelay;
            $sequence['delay'] = $delay;
            $delay = 0;

            $actionName = $sequence['action_name'];

            if ($actionName == 'fluentcrm_wait_times') {
                $delay = FunnelHelper::getDelayInSecond($sequence['settings']);
                $cDelay += $delay;
            }

            $sequence = apply_filters('fluentcrm_funnel_sequence_saving_' . $sequence['action_name'], $sequence, $funnel);

            if (Arr::get($sequence, 'type') == 'benchmark') {
                $delay = $sequence['delay'];
            }

            $sequence['created_by'] = get_current_user_id();

            $parentId = Arr::get($sequence, 'parent_id');

            if ($parentId) {
                $childs[$parentId][] = $sequence;
            } else {
                $createdSequence = FunnelSequence::create($sequence);
                $sequenceIds[] = $createdSequence->id;
                $oldNewMaps[$oldId] = $createdSequence->id;
            }
        }

        if ($childs) {
            foreach ($childs as $oldParentId => $childBlocks) {
                foreach ($childBlocks as $childBlock) {
                    $newParentId = Arr::get($oldNewMaps, $oldParentId);
                    if ($newParentId) {
                        $childBlock['parent_id'] = $newParentId;
                        $createdSequence = FunnelSequence::create($childBlock);
                        $sequenceIds[] = $createdSequence->id;
                    }
                }
            }
        }

        (new FunnelHandler())->resetFunnelIndexes();
        FunnelHelper::maybeMigrateConditions($funnel->id);

        return [
            'message' => __('Funnel has been successfully imported', 'fluent-crm'),
            'funnel'  => $funnel
        ];

    }

    public function deleteSubscribers(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $ids = $request->getSafe('subscriber_ids', [], 'intval');
        if (!$ids) {
            return $this->sendError([
                'message' => __('subscriber_ids parameter is required', 'fluent-crm')
            ]);
        }

        FunnelHelper::removeSubscribersFromFunnel($funnelId, $ids);

        return [
            'message' => __('Subscribed has been removed from this automation funnel', 'fluent-crm')
        ];
    }

    public function subscriberAutomations(Request $request, $subscriberId)
    {
        $automations = FunnelSubscriber::where('subscriber_id', $subscriberId)
            ->with([
                'funnel',
                'last_sequence',
                'next_sequence_item'
            ])
            ->orderBy('id', 'DESC')
            ->paginate();

        return [
            'automations' => $automations
        ];
    }

    public function updateSubscriptionStatus(Request $request, $funnelId, $subscriberId)
    {
        $status = $request->getSafe('status');
        if (!$status) {
            return $this->sendError([
                'message' => __('Subscription status is required', 'fluent-crm')
            ]);
        }

        $funnelSubscriber = FunnelSubscriber::where('funnel_id', $funnelId)
            ->where('subscriber_id', $subscriberId)
            ->first();

        if (!$funnelSubscriber) {
            return $this->sendError([
                'message' => __('No Corresponding report found', 'fluent-crm')
            ]);
        }

        if ($funnelSubscriber->status == 'completed') {
            return $this->sendError([
                'message' => __('The status already completed state', 'fluent-crm')
            ]);
        }

        $funnelSubscriber->status = $status;
        $funnelSubscriber->save();

        return [
            'message' => sprintf(esc_html__('Status has been updated to %s', 'fluent-crm'), $status)
        ];
    }

    private function maybeMigrateDB()
    {
        $sequence = \FluentCrm\App\Models\FunnelSequence::first();
        $isMigrated = false;
        global $wpdb;
        if ($sequence) {
            $attributes = $sequence->getAttributes();
            if (isset($attributes['parent_id'])) {
                $isMigrated = true;
            }
        } else {
            $isMigrated = $wpdb->get_col($wpdb->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='parent_id' AND TABLE_NAME=%s", $wpdb->prefix . 'fc_funnel_sequences'));
        }

        if (!$isMigrated) {
            $sequenceTable = $wpdb->prefix . 'fc_funnel_sequences';
            $wpdb->query("ALTER TABLE {$sequenceTable} ADD COLUMN `parent_id` bigint NOT NULL DEFAULT '0', ADD `condition_type` varchar(192) NULL AFTER `parent_id`");
        }
    }

    public function getEmailReports(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $emailSequences = FunnelSequence::where('funnel_id', $funnel->id)
            ->orderBy('sequence', 'ASC')
            ->where('action_name', 'send_custom_email')
            ->get();
        foreach ($emailSequences as $emailSequence) {
            $campaign = FunnelCampaign::where('id', $emailSequence->settings['reference_campaign'])->first();
            $emailSequence->campaign = [
                'subject' => $campaign->email_subject,
                'id'      => $campaign->id,
                'stats'   => $campaign->stats()
            ];
        }

        return [
            'email_sequences' => $emailSequences
        ];
    }

    public function saveEmailActionFallback(Request $request)
    {
        $funnelId = $request->get('funnel_id');
        return $this->saveEmailAction($request, $funnelId);
    }

    public function saveEmailAction(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);

        $settings = $request->getJson('action_data');
        $settings['action_name'] = 'send_custom_email';

        $funnelCampaign = Arr::get($settings, 'campaign', []);

        $funnelCampaignId = Arr::get($funnelCampaign, 'id');

        $data = Arr::only($funnelCampaign, array_keys(FunnelCampaign::getMock()));
        $data['settings']['mailer_settings'] = Arr::get($settings, 'mailer_settings', []);

        $type = 'created';

        if ($funnelCampaignId && $funnel->id == Arr::get($data, 'parent_id')) {
            // We have this campaign
            $data['settings'] = \maybe_serialize($data['settings']);
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ')';
            FunnelCampaign::where('id', $funnelCampaignId)->update($data);
            $type = 'updated';
        } else {
            $data['parent_id'] = $funnel->id;
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ')';
            $campaign = FunnelCampaign::create($data);
            $funnelCampaignId = $campaign->id;
        }

        if (Arr::get($funnelCampaign, 'design_template') == 'visual_builder') {
            $design = Arr::get($funnelCampaign, '_visual_builder_design', []);
            fluentcrm_update_campaign_meta($funnelCampaignId, '_visual_builder_design', $design);
        } else {
            fluentcrm_delete_campaign_meta($funnelCampaignId, '_visual_builder_design');
        }

        $refCampaign = FunnelCampaign::find($funnelCampaignId);

        return [
            'type'               => $type,
            'reference_campaign' => $funnelCampaignId,
            'campaign'           => Arr::only($refCampaign->toArray(), array_keys(FunnelCampaign::getMock()))
        ];
    }


    public function getSyncableContactCounts(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $latestAction = \FluentCrm\App\Models\FunnelSequence::where('funnel_id', $funnelId)
            ->orderBy('sequence', 'DESC')
            ->first();

        if (!$latestAction) {
            return [
                'syncable_count' => 0
            ];
        }

        $count = \FluentCrm\App\Models\FunnelSubscriber::where('funnel_id', $funnel->id)
            ->with(['subscriber'])
            ->where('status', 'completed')
            ->whereHas('subscriber', function ($q) {
                $q->where('status', 'subscribed');
            })
            ->whereHas('last_sequence', function ($q) use ($latestAction) {
                $q->where('action_name', '!=', 'end_this_funnel')
                    ->where('id', '!=', $latestAction->id)
                    ->where('sequence', '<', $latestAction->sequence);
            })->count();

        return [
            'syncable_count' => $count
        ];
    }

    public function syncNewSteps(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);

        if ($funnel->status != 'published') {
            return $this->sendError([
                'message' => __('Funnel status need to be published', 'fluent-crm')
            ]);
        }

        if (!defined('FLUENTCAMPAIGN_DIR_FILE')) {
            return $this->sendError([
                'message' => __('This feature require latest version of FluentCRM Pro version', 'fluent-crm')
            ]);
        }

        $cleanup = new \FluentCampaign\App\Hooks\Handlers\Cleanup();

        if (!method_exists($cleanup, 'syncAutomationSteps')) {
            return $this->sendError([
                'message' => __('This feature require latest version of FluentCRM Pro version', 'fluent-crm')
            ]);
        }

        $result = $cleanup->syncAutomationSteps($funnel);

        if (is_wp_error($result)) {
            return $this->sendError($result->get_error_messages());
        }

        return [
            'message' => __('Synced successfully', 'fluent-crm')
        ];
    }
}
