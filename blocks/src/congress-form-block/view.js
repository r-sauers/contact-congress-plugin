/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

( function( $ ) {
  "use strict";

  const prefix = "wp-block-congress-form-block__"; // id/class prefix.
  const ajaxurl = congressAjaxObj.ajaxurl;
  const captchaKey = congressCaptchaObj.clientSecret;
  let addressGlobal = "101 Fake St. Minneapolis, MN, USA";
  let repsGlobal = {}; // Stores data about representatives.
  let repsOrder = []; // Order of repsGlobal.
  let registerEmailNonce = undefined;

  /**
   * A helper function to make an Ajax call using a form submit evt.
   *
   * @param {jQuerySubmitEvent} evt should have data fields:
   *  - object {Obect}
   *  - success {object.Function}
   *  - error {object.Function}
   */
  function ajaxHandler( evt ) {
    evt.preventDefault();
    const form = evt.target;
    const formData = new FormData( form );
    let formMethod = form.attributes?.method?.nodeValue || "get";
    let formAction = form.attributes.action.nodeValue;

    if ( evt.originalEvent?.submitter?.attributes?.formaction ) {
      formAction = evt.originalEvent.submitter.attributes.formaction.value;
    }
    formData.set( "action", formAction );

    const object = evt.data.object || evt.data;
    const success = evt.data.success;
    const error = evt.data.error;

    if ( evt.data.onsubmit ) {
      object[evt.data.onsubmit.name]();
    }

    $.ajax({
      url: ajaxurl,
      type: formMethod,
      data: formData,
      processData: false,
      contentType: false
    })
      .done( ( data ) => object[success.name]( data, formAction ) )
      .fail( ( err ) => object[error.name]( err.responseJSON.error, { response: err, formAction: formAction }) );
  }

  /**
   * Returns the first representative that hasn't been sent an email.
   *
   * @return {Object|null}
   */
  function getFirstUnsentRep() {
    let rep = null;
    let i = 0;
    while ( null === rep && i < repsOrder.length ) {
      let _rep = repsGlobal[ repsOrder[i] ];
      if ( ! _rep.sent ) {
        rep = _rep;
      }
      i++;
    }
    return rep;
  }

  /**
   * Returns an example of a rep Object.
   *
   * @return {Object}
   */
  function getExampleRep() {
    return {
      title: "Senator",
      state: "MN",
      district: null,
      "first_name": "Jane",
      "last_name": "Doe"
    };
  }

  /**
   * The handler for the form that finds your representatives.
   */
  const getRepsHandlers = {

    /**
     * Handles successfully finding representatives.
     *
     * @param {Object} data is the data received from the request.
     */
    success: function( data ) {

      const reps = [];
      if ( "state" === data.level ) {
        reps.push( ...data.representatives );
      } else {
        reps.push( ...data.senators );
        reps.push( ...data.houseMembers );
      }

      if ( 0 === reps.length ) {
        getRepsHandlers.error( "Couldn't find representatives." );
        return;
      }


      //// Create representative list ////

      const $container = $( `#${prefix}rep-container` );
      $container.empty();
      repsGlobal = {};

      for ( const rep of reps.reverse() ) {

        const repEl = $( `#${prefix}rep-template` )[0].content.cloneNode( true );
        const $li = $( "<li>" );
        $li.append( repEl );
        $container.append( $li );

        if ( rep.img ) {
          $li.find( "img" ).attr( "src", rep.img );
        }
        $li.find( `.${prefix}rep-title` ).text( rep.title );
        $li.find( `.${prefix}rep-first` ).text( rep.first_name );
        $li.find( `.${prefix}rep-last` ).text( rep.last_name );
        $li.find( `.${prefix}rep-state` ).text( rep.state );
        if ( rep.district ) {
          $li.find( `.${prefix}rep-district` ).text( "District " + rep.district );
        } else {
          $li.find( `.${prefix}rep-district` ).empty();
        }
        $li.find( "button" ).attr( "repID", rep.id );

        repsGlobal[rep.id] = rep;
        repsGlobal[rep.id].sent = false;
        repsGlobal[rep.id].el = $li[0].children[0];
        repsOrder.push( rep.id );
      }

      registerEmailNonce = data.registerEmailNonce;


      //// Provide feedback on the results of the request. ////

      let rep = getFirstUnsentRep();
      $( `#${prefix}email-form button[type="submit"]` ).first().text( `Send to ${rep.first_name}!` );

      $( `#${prefix}email-form button[type="submit"]` ).first().prop( "disabled", false );

      $( `#${prefix}email-form input` ).first().focus();

      const $hint = $( `#${prefix}get-reps-form .${prefix}form-hint` ).last();
      $hint.empty();

      if (
        ( "state" === data.level && data.success ) ||
        ( "federal" === data.level && data.senateSuccess && data.houseSuccess )
      ) {

        $hint.text( "Successfully found your representatives! You can find them below." );
        $hint.toggleClass( `${prefix}form-success`, true );
        $hint.toggleClass( `${prefix}form-danger`, false );

      } else if (
        ( "state" === data.level && ! data.success ) ||
        ( "federal" === data.level && ! data.senateSuccess && ! data.houseSuccess )
      ) {

        $hint.append( document.createTextNode( "Failed to find your representatives! Please " ) );
        $( "<a>" )
          .attr( "href", `https://www.google.com/search?q=Find my representative ${data.stateCode}` )
          .attr( "target", "_blank" )
          .text( "search" )
          .appendTo( $hint );
        $hint.append( document.createTextNode( " for your representatives, and select from the list below." ) );
        $hint.toggleClass( `${prefix}form-success`, false );
        $hint.toggleClass( `${prefix}form-danger`, true );

      } else if ( "federal" === data.level && ! data.senateSuccess && data.houseSuccess ) {

        $hint.append( document.createTextNode( "We found your members in the House of Representatives, but failed to find your senators! Please " ) );
        $( "<a>" )
          .attr( "href", `https://www.google.com/search?q=Find my representative ${data.stateCode}` )
          .attr( "target", "_blank" )
          .text( "search" )
          .appendTo( $hint );
        $hint.append( document.createTextNode( " for your senators, and select from the list below." ) );
        $hint.toggleClass( `${prefix}form-success`, false );
        $hint.toggleClass( `${prefix}form-danger`, true );

      } else if ( "federal" === data.level && data.senateSuccess && ! data.houseSuccess ) {

        $hint.append( document.createTextNode( "We found your senators, but failed to find the your members in the House of Representatives! Please " ) );
        $( "<a>" )
          .attr( "href", `https://www.google.com/search?q=Find my representative ${data.stateCode}` )
          .attr( "target", "_blank" )
          .text( "search" )
          .appendTo( $hint );
        $hint.append( document.createTextNode( " for your representatives, and select from the list below." ) );
        $hint.toggleClass( `${prefix}form-success`, false );
        $hint.toggleClass( `${prefix}form-danger`, true );

      }
    },


    /**
     * Handles failure to find representatives.
     *
     * @param {string} err is the request error message.
     */
    error: function( err ) {

      const $div = $( "<div>" );
      $div.css( "width", "100%" );
      $div.css( "text-align", "center" );
      $div.text( "No Representatives." );

      const $container = $( `#${prefix}rep-container` );
      $container.empty();
      $container.append( $div );

      $( `#${prefix}email-form button[type="submit"]` ).first().prop( "disabled", true );

      const $hint = $( `#${prefix}get-reps-form .${prefix}form-hint` ).last();
      $hint.empty();
      $hint.text( err );
      $hint.toggleClass( `${prefix}form-success`, false );
      $hint.toggleClass( `${prefix}form-danger`, true );
    },

    /**
     * Adds loading sign on submit.
     */
    onsubmit: function() {
      const $hint = $( `#${prefix}get-reps-form .${prefix}form-hint` ).last();
      $hint.empty();
      $( "<div>" )
        .addClass( `${prefix}loading` )
        .appendTo( $hint );
    }
  };

  /**
   * Updates the email preview.
   */
  function updateEmailPreview() {
    const $emailBody = $( `#${prefix}email-body` );
    const $emailTemplate = $( `#${prefix}email-template` );
    let rep = getFirstUnsentRep() || getExampleRep();
    let emailForm = $( `#${prefix}email-form` )[0];
    let template = $emailTemplate.val();
    template = template.replaceAll( /\[\[REP_FIRST\]\]/ig, rep.first_name );
    template = template.replaceAll( /\[\[REP_LAST\]\]/ig, rep.last_name );
    template = template.replaceAll( /\[\[REP_TITLE\]\]/ig, rep.title );
    template = template.replaceAll( /\[\[ADDRESS\]\]/ig, addressGlobal );
    if ( emailForm.firstName.value ) {
      template = template.replaceAll( /\[\[SENDER_FIRST\]\]/ig, emailForm.firstName.value );
    }
    if ( emailForm.lastName.value ) {
      template = template.replaceAll( /\[\[SENDER_LAST\]\]/ig, emailForm.lastName.value );
    }
    $emailBody.val( template );
  }

  /**
   * Handles the form submission of the email form.
   *
   * @param {jQuerySubmitEvent} evt
   */
  function handleEmailSend( evt ) {
    evt.preventDefault();

    const $form = $( `#${prefix}get-reps-form` );
    const campaignID = parseInt( $form[0].campaignID.value );

    grecaptcha.ready( function() {
      grecaptcha.execute( captchaKey, { action: "submit" }).then( function( token ) {

        $.post(
          ajaxurl,
          {
            _wpnonce: registerEmailNonce,
            action: "register_email",
            "g-recaptcha-response": token,
            campaignID: campaignID
          },
          function() {}
        );
      });
    });

    // Get the representative.
    let submittedFromRep = true;
    let repID = evt?.originalEvent?.submitter?.attributes?.repID?.value;
    if ( ! repID ) {
      submittedFromRep = false;
      let rep = getFirstUnsentRep();
      if ( null === rep ) {
        return;
      }
      repID = rep.id;
    }
    const rep = repsGlobal[repID];
    const repEl = rep.el;
    const repBtn = $( repEl ).find( "button" )[0];

    let isGmail = ( -1 !== evt.originalEvent.target.email.value.search( "@gmail\.com" ) );
    let url = "mailto:";
    let firstIter = true;
    for ( const staffer of rep.staffers ) {
      if ( ! firstIter ) {
        url += ", ";
      }
      url += staffer.email;
      firstIter = false;
    }
    url += "?subject=" + evt.originalEvent.target.subject.value;
    url += "&body=" + evt.originalEvent.target.body.value;
    url += "&email=" + evt.originalEvent.target.email.value;
    url = encodeURI( url );
    window.open( url, "_blank" );

    $( repBtn ).prop( "disabled", true );
    $( repBtn ).text( "Sent!" );

    rep.sent = true;

    let allRepsSent = true;
    for ( let repID of repsOrder ) {
      if ( ! repsGlobal[repID].sent ) {
        allRepsSent = false;
      }
    }

    const $submitBtn = $( `#${prefix}email-form button[type="submit"]` ).first();
    if ( allRepsSent ) {
      $submitBtn.prop( "disabled", true );
      $submitBtn.text( "Sent!" );
    } else {
      let rep = getFirstUnsentRep();
      $submitBtn.text( `Send to ${rep.first_name}!` );
    }

    updateEmailPreview();
  };

  /**
   * Initializes the event handlers and state for the email template and email preview.
   */
  function initEmailPreview() {

    const $emailBody = $( `#${prefix}email-body` );
    const $emailTemplate = $( `#${prefix}email-template` );
    const $previewToggle = $( `#${prefix}preview-toggle` );
    let scrolling = ""; // Keeps track of whether the body is being scrolled or the template is.

    updateEmailPreview();
    $emailBody.show();
    $emailTemplate.hide();
    $previewToggle.text( "View & Edit Raw Email Template" );
    $previewToggle.toggleClass( `${prefix}preview-open`, true );
    $previewToggle.on( "click", function() {
      if ( $previewToggle.hasClass( `${prefix}preview-open` ) ) {
        $previewToggle.text( "View Email Preview" );
        $previewToggle.toggleClass( `${prefix}preview-open`, false );
        $emailTemplate.show();
        $emailBody.hide();
      } else {

        updateEmailPreview();

        $previewToggle.text( "View & Edit Raw Email Template" );
        $previewToggle.toggleClass( `${prefix}preview-open`, true );
        $emailTemplate.hide();
        $emailBody.show();
      }
    });
    $emailBody.on( "scroll", function( evt ) {
      if ( "" === scrolling ) {
        scrolling = "body";
      }
      if ( "body" === scrolling ) {
        $emailTemplate[0].scrollTo({
          top: evt.target.scrollTop
        });
      }
    });
    $emailTemplate.on( "scroll", function( evt ) {
      if ( "" === scrolling ) {
        scrolling = "template";
      }
      if ( "template" === scrolling ) {
        $emailBody[0].scrollTo({
          top: evt.target.scrollTop
        });
      }
    });
    $emailTemplate.on( "scrollend", () => scrolling = "" );
    $emailBody.on( "scrollend", () => scrolling = "" );

    let emailForm = $( `#${prefix}email-form` )[0];
    $( emailForm.firstName ).on( "input", function() {
      updateEmailPreview();
    });
    $( emailForm.lastName ).on( "input", function() {
      updateEmailPreview();
    });
  }

  // Get the google location session.
  $.post(
    ajaxurl,
    {
      action: "get_session",
      _wpnonce: congressAjaxObj.sessionNonce
    },
    function( data ) {
      const sessionToken = data.uuid;
      const sessionTokenName = data.name;
      document.cookie = `${sessionTokenName}=${sessionToken}; path=/`;
    }
  );

  // Initialize the address search.
  $( `#${prefix}street-address` ).select2({
    minimumInputLength: 4,
    ajax: {
      url: ajaxurl,
      data: function( params ) {
        let query = {
          action: "autocomplete",
          _wpnonce: congressAjaxObj.autocompleteNonce,
          address: params.term
        };

        return query;
      },
      processResults: function( data ) {

        let results = $.map( data.suggestions, function( obj ) {
          obj.id = obj.placePrediction.placeId;
          obj.text = obj.placePrediction.text.text;
          return obj;
        });

        return {
          results: results
        };
      },
      transport: async function( params, success, failure ) {

        let token = await new Promise( ( res, rej ) => {
          grecaptcha.ready( function() {
            grecaptcha.execute( captchaKey, { action: "submit" }).then( function( token ) {
              res( token );
            }).catch( () => {
              rej();
            });
          });
        });

        params.data["g-recaptcha-response"] = token;
        params.method = "POST";
        let $request = $.ajax( params );

        $request.then( success );
        $request.fail( failure );

        return $request;
      }
    }
  }).on( "select2:select", function( e ) {
    const $form = $( `#${prefix}get-reps-form` );
    $form[0].placeId.value = e.params.data.id;
    $form.trigger( "submit" );
    addressGlobal = e.params.data.text;
  });

  $( `#${prefix}get-reps-form` ).on( "submit", null, getRepsHandlers, ajaxHandler );

  $( `#${prefix}email-form` ).on( "submit", handleEmailSend );

  initEmailPreview();

  // Fix select2 width
  $( window ).on( "resize", function() {
    let $emailForm = $( `#${prefix}email-form` );
    let width = $emailForm.find( "input" ).first().outerWidth();
    $( ".select2-container" ).css( "width", width );
  });

}( jQuery ) );
