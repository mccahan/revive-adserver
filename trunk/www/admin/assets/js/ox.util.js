// Turns an input into an autosubmit one
jQuery.fn.submitOnChange = function() {
  return this.each(function ()
  {
    $(this).bind("change", function()
    {
      if (this.form) {
        this.form.submit();
      }
    });
  });
};

// All inputs marked with "submit-on-change" class will be
// automatically turned into autosubmit inputs
$(document).ready(function() {
  $("select.submit-on-change").submitOnChange();
});

// Additional jQuery effect
jQuery.fn.slideFadeOut = function(speed, callback)
{
  return this.animate({height: 'hide', opacity: 'hide', marginTop: 'hide', marginBottom: 'hide'}, speed, callback);
};

// Additional jQuery effect
jQuery.fn.slideFadeIn = function(speed, callback)
{
  return this.animate({height: 'show', opacity: 'show', marginTop: 'show', marginBottom: 'show'}, speed, callback);
};

// Shows or hides an element depending on the parameter
jQuery.fn.visible = function(visible)
{
  return this.each(function() {
    if (visible) {
      $(this).show();
    }
    else {
      $(this).hide();
    }
  });
};

// Shows or hides an element depending on the parameter
jQuery.fn.selectedText = function(visible)
{
  if (this.size() == 0) {
    return;
  }
  var select = this[0];
  return select.options[select.selectedIndex].text; 
};

// Automatically installs validation on forms with the "validate" class
// Also adds some custom validation rules
$(document).ready(function () {
  // The validation plugin does not apply the validate() function to
  // all jQuery elements (kind of weird...), so we must use an explicit each()
  $("form.validate").each(function() {
    $(this).validate();
  });
});


/** 
 * A function for making dialog-confirmed links. Note that
 * configuration-dialog.html must be included which contains
 * the actual confirmation dialog code.
 */
jQuery.fn.confirmedLink = function(triggerLinkClass, closeIdPrefix)
{
  $("#" + closeIdPrefix + "confirmation-dialog").jqm({
      modal: true,
      overlay: 40,
      trigger: "." + triggerLinkClass,
      onShow: function(hash) {
        $("#" + closeIdPrefix + "cd-submit").one("click", function() {
          location.href = hash.t.href;
        });
        hash.w.fadeIn("fast");
      }
  }).jqmAddClose("#" + closeIdPrefix + "cd-cancel");
};

/**
 * Converts the provided links (pointing at legal documents) into
 * a modal popup displaying the same contents.
 */
jQuery.terms = function(triggerLinksSelector, closeIdPrefix) {
  if($.browser.msie && (parseInt($.browser.version) == 6)) {
    // Fall back to opening in a new window on IE6.
    return this;
  }
 
  $("#" + closeIdPrefix + "terms-dialog").jqm({
      modal: true,
      overlay: 40,
      trigger: triggerLinksSelector,
      onShow: function(hash) {
        var windowHeight = $(window).height();
        var topOffset = 0.15;
        var extraHeader = 70;
        var $termsContents = $("#" + closeIdPrefix + "terms-contents");
        $termsContents.height(windowHeight * (1 - 2 * topOffset) - extraHeader);
        
        $termsContents.html("<a href='" + hash.t.href + "' target='_blank'>" + hash.t.title + "</a>");
        $termsContents.load(hash.t.href, null, function() { 
          this.scrollTop = 0;
        }); 
        $("#" + closeIdPrefix + "terms-title").html("&nbsp;&nbsp;" + hash.t.title);
        $("#" + hash.t.id + "c").attr("checked", false);
        $("#" + closeIdPrefix + "terms-submit").one("click", function() {
          $("#" + hash.t.id + "c").attr("checked", true);
          $("#" + closeIdPrefix + "terms-dialog").jqmHide();
        });
        hash.w.fadeIn("fast");
      }
  }).jqmAddClose("#" + closeIdPrefix + "terms-cancel");
};

/**
 * To the first selected checkbox attaches an event handler that shows/hides the
 * provided content depending on whether the checkbox is checked or not.
 */
jQuery.fn.toggleContent = function(checkedContentSelector, uncheckedContentSelector) {
  return this.eq(0).click(function() {
    if (this.checked) {
      $(checkedContentSelector).show();
      $(uncheckedContentSelector).hide();
    } else {
      $(checkedContentSelector).hide();
      $(uncheckedContentSelector).show();
    }
  }).end();
};