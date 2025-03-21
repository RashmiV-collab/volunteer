<?php

namespace FluentFormPro\classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


use FluentForm\App\Models\FormMeta;
use FluentForm\App\Modules\Form\FormDataParser;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\App\Services\Integrations\GlobalNotificationManager;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentFormPro\Components\Post\PostFormHandler;

class ResendNotificationHandler
{
    public function init()
    {
        add_action('wp_ajax_ffpro-resent-email-notification', array($this, 'resendEmail'));
        add_action('wp_ajax_ffpro_get_integration_feeds', array($this, 'getFeeds'));
        add_action('wp_ajax_ffpro_post_integration_feed_replay', array($this, 'replayFeed'));
    }

    public function resendEmail()
    {
        $notificationId = intval(ArrayHelper::get($_REQUEST, 'notification_id'));
        $formId = intval(ArrayHelper::get($_REQUEST, 'form_id'));
        $entryId = intval(ArrayHelper::get($_REQUEST, 'entry_id'));

        $this->verify($formId);

        $entryIds = [];
        if (ArrayHelper::get($_REQUEST, 'entry_ids')) {
            $entryIds = array_filter(ArrayHelper::get($_REQUEST, 'entry_ids', []), 'intval');
        }

        $sendToType = sanitize_text_field(ArrayHelper::get($_REQUEST, 'send_to_type'));
        $customRecipient = sanitize_text_field(ArrayHelper::get($_REQUEST, 'send_to_custom_email'));

        $feed = wpFluent()->table('fluentform_form_meta')
                          ->where('id', $notificationId)
                          ->where('meta_key', 'notifications')
                          ->where('form_id', $formId)
                          ->first();

        if (!$feed) {
            wp_send_json_error([
                'message' => __('Sorry, No notification found!')
            ], 423);
        }

        $feed->value = \json_decode($feed->value, true);

        $form = wpFluent()->table('fluentform_forms')
                          ->where('id', $formId)
                          ->first();

        if ($entryId) {
            $this->resendEntryEmail($entryId, $feed, $sendToType, $customRecipient, $form);
        } else if ($entryIds) {
            foreach ($entryIds as $entry_id) {
                $this->resendEntryEmail($entry_id, $feed, $sendToType, $customRecipient, $form);
            }
        }

        wp_send_json_success([
            'message' => __('Notification successfully resent', 'fluentformpro')
        ], 200);
    }

    public function getFeeds()
    {
        $formId = intval(ArrayHelper::get($_REQUEST, 'form_id'));
        $entryId = intval(ArrayHelper::get($_REQUEST, 'entry_id'));
        $this->verify($formId);

        wp_send_json_success([
            'feeds' => $this->getFormattedFeeds($formId, $entryId)
        ], 200);
    }

    public function replayFeed()
    {
        $requestLogIds = ArrayHelper::get($_REQUEST, 'logIds');
        $this->handleReplayFeed($requestLogIds);
    }

    private function handleReplayFeed($requestLogIds)
    {
        $isMultipleActions = rest_sanitize_boolean(ArrayHelper::get($_REQUEST, 'multiple_actions', false));

        add_action(
            'fluentform/integration_action_result',
            function($feed, $status, $message) use ($requestLogIds, $isMultipleActions) {
                foreach ($requestLogIds as $logIds) {
                    $feedId = intval(ArrayHelper::get($feed, 'id'));
                    $requestFeedId = intval(ArrayHelper::get($logIds, 'feed_id'));

                    if ($feedId != $requestFeedId) {
                        continue;
                    }

                    $actionId = intval(ArrayHelper::get($logIds, 'action_id'));
                    $feed = FormMeta::where('id', $requestFeedId)->first();

                    if ($feed && $feed->value) {
                        $feedValue = json_decode($feed->value, true);
                        $isDisabled = ArrayHelper::get($feedValue, 'enabled') == 0;
                        if ($isDisabled && !$isMultipleActions) {
                            $message = __('Feed is disabled', 'fluentformpro');
                            wp_send_json_error(['message' => $message], 423);
                            return;
                        }
                    }

                    // Handle status
                    if ($status == 'failed') {
                        $message = empty($message) ? __('Something is wrong when processing the feed', 'fluentformpro') : $message;
                    } elseif ($status == 'success') {
                        $message = empty($message) ? __('The selected feed has been successfully fired', 'fluentformpro') : $message;
                    }

                    // Update scheduled actions
                    wpFluent()->table('ff_scheduled_actions')
                              ->where('id', $actionId)
                              ->update([
                                  'status' => $status,
                                  'note' => $message,
                                  'updated_at' => current_time('mysql')
                              ]);

                    // Send JSON response
                    if ($isMultipleActions) {
                        wp_send_json_success([
                            'message' => __('Multiple actions have been fired', 'fluentformpro')
                        ], 200);
                    }

                    if ($status == 'failed') {
                        wp_send_json_error(['message' => $message], 423);
                    } else {
                        wp_send_json_success(['message' => $message], 200);
                    }
                }
            },
            1,
            3
        );

        foreach ($requestLogIds as $logIds) {
            if (ArrayHelper::get($logIds, 'integration_enabled') == 'false') {
                wp_send_json_error([
                    'message' => __('Please enable the integration', 'fluentformpro')
                ], 423);
            }

            $entryId = intval(ArrayHelper::get($logIds, 'entry_id'));
            $formId = intval(ArrayHelper::get($logIds, 'form_id'));
            $feedId = intval(ArrayHelper::get($logIds, 'feed_id'));

            $verifyCondition = sanitize_text_field(ArrayHelper::get($_REQUEST, 'verify_condition')) == 'yes';

            $this->verify($formId);

            $form = wpFluent()->table('fluentform_forms')
                              ->where('id', $formId)
                              ->first();

            $feed = wpFluent()->table('fluentform_form_meta')
                              ->where('form_id', $formId)
                              ->where('id', $feedId)
                              ->first();

            if (!$feed) {
                $message = __('Invalid Feed ID', 'fluentformpro');

                if (!$isMultipleActions) {
                    wp_send_json_error([
                        'message' => $message
                    ], 423);
                }
            }

            $entry = $this->getEntry($entryId, $form);
            $formData = json_decode($entry->response, true);
            $parsedValue = json_decode($feed->value, true);

            $originalParsedValue = $parsedValue;

            $processedValues = $parsedValue;
            unset($processedValues['conditionals']);
            $processedValues = ShortCodeParser::parse($processedValues, $entryId, $formData);

            if ($verifyCondition) {
                $isMatched = (new GlobalNotificationManager(wpFluentForm()))->checkCondition($originalParsedValue,
                    $formData, $entryId);

                if (!$isMatched) {
                    $message = __('Conditions did not satisfy for this feed', 'fluentformpro');

                    if (!$isMultipleActions) {
                        wp_send_json_error([
                            'message' => $message
                        ], 423);
                    }
                }
            }

            $item = [
                'id'              => $feed->id,
                'meta_key'        => $feed->meta_key,
                'settings'        => $parsedValue,
                'processedValues' => $processedValues
            ];

            $action = 'fluentform/integration_notify_' . $item['meta_key'];

            add_filter('fluentform/integration_notify_throw_error', '__return_true');

            if ($item['meta_key'] == 'postFeeds') {
                (new PostFormHandler())->createPostFromFeed($processedValues, $entryId, $formData, $form);
            } else {
                do_action($action, $item, $formData, $entry, $form);
            }
        }

        wp_send_json_success([
            'message' => __('The selected feeds has been successfully fired.', 'fluentformpro')
        ], 200);
    }

    private function resendEntryEmail($entryId, $feed, $sendToType, $customRecipient, $form)
    {
        $parsedValue = $feed->value;
        $entry = wpFluent()->table('fluentform_submissions')
            ->where('id', $entryId)
            ->first();

        $formData = \json_decode($entry->response, true);
        ShortCodeParser::resetData();
        $processedValues = ShortCodeParser::parse($parsedValue, $entry, $formData, $form, false, $feed->meta_key);

        if ($sendToType == 'custom') {
            $processedValues['bcc'] = '';
            $processedValues['sendTo']['email'] = $customRecipient;
            $processedValues['sendTo']['type'] = 'custom';
        }

        $attachments = [];
        if (!empty($processedValues['attachments']) && is_array($processedValues['attachments'])) {
            foreach ($processedValues['attachments'] as $name) {
                $fileUrls = ArrayHelper::get($formData, $name);
                if ($fileUrls && is_array($fileUrls)) {
                    foreach ($fileUrls as $url) {
                        $filePath = str_replace(
                            site_url(''),
                            wp_normalize_path(untrailingslashit(ABSPATH)),
                            $url
                        );
                        if (file_exists($filePath)) {
                            $attachments[] = $filePath;
                        }
                    }
                }
            }
        }

        $attachments = apply_filters_deprecated(
            'fluentform_email_attachments',
            [
                $attachments,
                $processedValues,
                $formData,
                $entry,
                $form
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/email_attachments',
            'Use fluentform/email_attachments instead of fluentform_email_attachments.'
        );
        // let others to apply attachments
        $attachments = apply_filters('fluentform/email_attachments', $attachments, $processedValues, $formData, $entry, $form);

        $processedValues['attachments'] = $attachments;

        $enabledFeed = [
            'id'              => $feed->id,
            'meta_key'        => $feed->meta_key,
            'settings'        => $parsedValue,
            'processedValues' => $processedValues
        ];

        add_action('wp_mail_failed', function ($error) {
            $reason = $error->get_error_message();
            wp_send_json_error([
                'message' => __("Email Notification failed to sent. Reason: ", 'fluentformpro') . $reason
            ], 423);
        }, 10, 1);

        $notifier = wpFluentForm()->make(
            'FluentForm\App\Services\FormBuilder\Notifications\EmailNotification'
        );
        $notifier->notify($enabledFeed['processedValues'], $formData, $form, $entry->id);
    }

    private function verify($formId = false)
    {
        if (!$formId) {
            $formId = intval(ArrayHelper::get($_REQUEST,'form_id'));
        }
        \FluentForm\App\Modules\Acl\Acl::verify('fluentform_entries_viewer', $formId);
    }

    private function getFormattedFeeds($formId, $entryId)
    {
        $activeFeeds = apply_filters_deprecated(
            'fluentform_global_notification_active_types',
            [
                [],
                $formId
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/global_notification_active_types',
            'Use fluentform/global_notification_active_types instead of fluentform_global_notification_active_types.'
        );
        // Let's find the feeds that are available for this form
        $feedKeys = apply_filters('fluentform/global_notification_active_types', $activeFeeds, $formId);

        if (!$feedKeys) {
            return [];
        }

        unset($feedKeys['user_registration_feeds']);
        unset($feedKeys['notifications']);

        $feedMetaKeys = array_keys($feedKeys);
        $feedMetaKeys[] = 'postFeeds';

        $feeds = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->whereIn('meta_key', $feedMetaKeys)
            ->orderBy('id', 'ASC')
            ->get();

        if (!$feeds) {
            return [];
        }

        $formattedFeeds = [];
        foreach ($feeds as $feed) {
            $parsedValue = json_decode($feed->value, true);
            if (!$parsedValue) {
                continue;
            }

            $conditionSettings = ArrayHelper::get($parsedValue, 'conditionals');
            if (
                !$conditionSettings ||
                !ArrayHelper::isTrue($conditionSettings, 'status') ||
                !count(ArrayHelper::get($conditionSettings, 'conditions'))
            ) {
                $hasCondition = false;
            } else {
                $hasCondition = true;
            }

            $feedName = ArrayHelper::get($parsedValue, 'name');
            if (!$feedName) {
                $feedName = ArrayHelper::get($parsedValue, 'feed_name');
            }

            $status = ArrayHelper::isTrue($parsedValue, 'enabled');
            if (!isset($parsedValue['enabled'])) {
                $status = ArrayHelper::isTrue($parsedValue, 'feed_status');
            }

            $feedData = [
                'id'            => $feed->id,
                'has_condition' => $hasCondition,
                'name'          => $feedName,
                'enabled'       => $status,
                'provider'      => $feed->meta_key,
                'feed'          => $parsedValue
            ];

            $scheduledActions = wpFluent()->table('ff_scheduled_actions')
                                          ->where('origin_id', $entryId)
                                          ->where('feed_id', $feed->id)
                                          ->get();

            if ($scheduledActions) {
                foreach ($scheduledActions as $action) {
                    $feedData['action_id'] = $action->id;
                }
            }

            $feedData = apply_filters_deprecated(
                'fluentform_global_notification_feed_' . $feed->meta_key,
                [
                    $feedData,
                    $formId
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/global_notification_feed_' . $feed->meta_key,
                'Use fluentform/global_notification_feed_' . $feed->meta_key . ' instead of fluentform_global_notification_feed_' . $feed->meta_key
            );

            $formattedFeeds[] = apply_filters('fluentform/global_notification_feed_' . $feed->meta_key, $feedData, $formId);
        }

        return $formattedFeeds;
    }

    private function getEntry($id, $form)
    {
        $submission = wpFluent()->table('fluentform_submissions')->find($id);
        $formInputs = FormFieldsParser::getEntryInputs($form, ['admin_label', 'raw']);
        return FormDataParser::parseFormEntry($submission, $form, $formInputs);
    }
}
