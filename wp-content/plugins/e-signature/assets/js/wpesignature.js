"use strict";

/**
 *  WP E-Signature core class for dom handle 
 *  @since 1.5.7.0 
 */


function wpEsig(selector) 
{
    // check for instance if true return with selector. 
    if (!(this instanceof wpEsig)) 
    {
        return new wpEsig(selector); // return new object.  
    }
    // select a document element 
    this.selector = document.querySelector(selector);
}

// wp esig prototype object to handle  html dom.  
wpEsig.prototype = {
  // This on method to handle document event.
  on: function (eventType, callBack) {
    if (this.selector) {
      this.selector.addEventListener(
        eventType,
        function (event) {
          callBack.call(event);
          return this;
        },
        false
      );
    }
  },
  // show method to show html element
  show: function () {
    this.selector.style.display = "flex"; // it will show element in display flex mode.
  },
  // Hide an element  from dom
  hide: function () {
    this.selector.style.display = "none";
  },
  // This method remove a html element from dom
  remove: function () {
    this.selector.remove();
  },
  // Insert html content into document.
  html: function (content) {
    this.selector.innerHTML = content; // It will insert new content or replace existing html content.
  },
  // return value for wp esignature input element
  value: function () {
    return this.selector.value;
  },
  // add a custom css
  css: function (cssPropertyName, value) {
    this.selector.style[cssPropertyName] = value;
  },
  // add a custom css
  removeCss: function (cssPropertyName) {
    this.selector.style[cssPropertyName] = null;
  },
  // add new css class
  addClass: function (className) {
    this.selector.classList.add(className);
  },
  // check for existing css class
  hasClass: function (className) {
    return this.selector.classList.contains(className);
  },
  // remove new css class 
  removeClass: function (className) {
    // check for existing css class
    if (this.selector.classList.contains(className)) {
      this.selector.classList.remove(className);
    }
  },
  // replace existing css class with new one
  replaceClass: function (className) {
    this.selector.className = className;
  },
  // insert html content Just inside the element, before its first child.
  append: function (htmlContent) {
    this.selector.insertAdjacentHTML("afterbegin", htmlContent);
  },
}; 


/**
 * Checking next element id by selector provided. 
 * @param {*} elementId 
 */
wpEsig.prototype.checkNextElement = function(elementId)
{   
    // check if next eleme is not null
    if (document.getElementById(this.selector.id).nextElementSibling !== null) 
    {
        // Grab the id from next sibling 
        var idExists = document.getElementById(this.selector.id).nextElementSibling.id;
        // compare with provided element id and return true 
        if (idExists && idExists === elementId) {
            return true
        }
    }
    // If no element id found return false.  
    return false;
}

/**
 * Insert a validation message for given element id 
 * @param {*} message 
 * @param {*} border 
 */

wpEsig.prototype.insertAfterValidationMsg = function(message,border=false)
{
    if (!this.checkNextElement("esig-form-input-validation")) 
    {
        document.getElementById(this.selector.id).insertAdjacentHTML(
            "afterend", '<div id="esig-form-input-validation" class="esig-input-error"> <span class="esig-icon-esig-alert"></span> <span class="error-msg">' + message +  '</span></div>'
        );

        if(border) 
        {
            this.css.call(this,"borderColor","#c51244");
        }   
        
    }
    
}

/**
 * Remove validation message if it exists. 
 * @param {*} border 
 */
wpEsig.prototype.removeValidationMsg = function(border=false)
{
    if (this.checkNextElement.call(this, "esig-form-input-validation")) 
    {
        this.selector.nextElementSibling.remove();
    }
    // if border tru then remove it 
    if(border)
    {
        this.removeCss.call(this, "borderColor");
    }
}

