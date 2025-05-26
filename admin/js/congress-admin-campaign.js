( function( $ ) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  /**
   * Extension of string to transform text to Proper Case e.g. 'hello world' => 'Hello World'.
   *
   * Credit To: https://stackoverflow.com/questions/196972/convert-string-to-title-case-with-javascript
   */
  String.prototype.toProperCase = function() {
      return this.replace( /\w\S*/g, function( txt ) {
        return txt.charAt( 0 ).toUpperCase() + txt.slice( 1 ).toLowerCase();
      });
  };

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

    const object = evt.data.object;
    const success = evt.data.success;
    const error = evt.data.error;

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
   * A helper function to make an Ajax call that prompts the user to
   * select files to fill hidden file fields, then submits the form.
   *
   * @param {jQuerySubmitEvent} evt should have data fields:
   *  - object {Obect}
   *  - success {object.Function}
   *  - error {object.Function}
   *  @param {number} filesSelected should be ignored.
   */
  function ajaxUploadHandler( evt, filesSelected = 0 ) {

    evt.preventDefault();
    const form = evt.target;
    const $fileFields = $( form ).find( "input[type='file']" );

    // Trigger file selection if needed.
    if ( filesSelected != $fileFields.length ) {
      const $nextField = $fileFields.eq( filesSelected );
      $nextField.on( "input", function() {
        $( form ).trigger( "submit", filesSelected + 1 );
      });
      $( $nextField ).trigger( "click", true );
      return;
    }

    // Ensure files exist
    let missingFile = false;
    $fileFields.each( function() {
      if ( this.required && "" === this.value ) {
        missingFile = true;
      }
    });
    if ( missingFile ) {
      evt.data.object[evt.data.error.name]( "No file selected!" );
      return;
    }

    ajaxHandler( evt );
  }

  /**
   * Helps initialize html and handle edit events.
   */
  class EmailTemplate {

    /**
     * The id of the template's campaign.
     *
     * @type {number}
     */
    _campaignID;

    /**
     * The id of the email template.
     *
     * @type {number}
     */
    _id;

    /**
     * The subject of the email.
     *
     * @type {string}
     */
    _subject;

    /**
     * If this is true, it should be sent to congress members supporting the campaign.
     *
     * @type {boolean}
     */
    _favorable;

    /**
     * The body of the email template.
     *
     * @type {string}
     */
    _template;

    /**
     * The form to handle edits to the email template.
     *
     * @type {jQueryFormElement}
     */
    _$form;

    /**
     * The root of the DOM displaying the email template.
     *
     * @type {jQueryLIElement}
     */
    _$root;

    /**
     * What textarea is currently being scrolled.
     *
     * @type {'template'|'preview'|''}
     */
    _scrolling;

    /**
     * Creates a DOW template for an email template.
     */
    static getTemplate() {
      const template = $( "#congress-campaign-email-template" )[0];
      return template.content.cloneNode( true );
    }

    /**
     * Constructs the campaign.
     *
     * @param {number} campaignID The id of the template's campaign.
     * @param {number} id The id of the email template.
     * @param {string} subject The subject of the email.
     * @param {boolean} favorable If this is true, it should be sent to congress members supporting the campaign.
     * @param {string} template The body of the email template.
     */
    constructor( campaignID, id, subject, favorable, template ) {
      this._campaignID = campaignID;
      this._id = id;
      this._subject = subject;
      this._favorable = favorable;
      this._template  = template ;
      this._scrolling = "";
    }

    /**
     * Creates a DOM element for the email template and appends it to $root.
     *
     * @param {jQueryLIElement} $root is the DOM root.
     * @param {string} editNonce is the nonce for edit/delete requests.
     */
    createDOM( $root, editNonce ) {

      const dom = EmailTemplate.getTemplate();
      $root.append( dom );

      this._$root = $root;
      this._$form = $root.find( ".congress-campaign-email-edit-form" ).first();
      this.setID( this._id, this._campaignID );
      this.setSubject( this._subject );
      this.setTemplate( this._template );
      this.setFavorable( this._favorable );
      this._initForm( editNonce );
    }

    /**
     * Sets the database id for the email template.
     *
     * @param {number} id
     * @param {number} campaignID
     */
    setID( id, campaignID ) {

      // handle form labels
      const I = this;
      this._$form.find( "label" ).each( function() {
        const oldID = $( this ).attr( "for" );
        let newID = oldID
          .replace( "campaign-id", campaignID )
          .replace( "email-id", id );
        $( this ).attr( "for", newID );
        I._$form.find( "#" + oldID ).attr( "id", newID );
      });

      // handle form id
      this._$form[0].id.value = id;
      this._$form[0]["campaign_id"].value = this._campaignID;

      // handle title
      this._$root.find( "h3" ).text( `Template ${id}` );

      this._id = id;
      this._campaignID = campaignID;
    }

    /**
     * Sets the subject of the email.
     *
     * @param {string} value
     */
    setSubject( value ) {
      this._$form[0].subject.value = value;
      this._subject = value;
    }

    /**
     * Sets the template body of the email.
     *
     * The template can have the following placeholders:
     * [[REP_FIRST]], [[REP_LAST]], [[REP_TITLE]], [[SENDER_FIRST]], [[SENDER_LAST]]
     *
     * @param {string} value
     */
    setTemplate( value ) {
      this._$form[0].template.value = value;

      const rep = {
        title: "Senator",
        firstName: "Amy",
        lastName: "Klobuchar"
      };

      const sender = {
        firstName: "John",
        lastName: "Doe"
      };

      const $template = $( this._$form[0].template );
      const $preview = $( this._$form[0].preview );

      let template = $template.val();
      template = template.replaceAll( /\[\[REP_FIRST\]\]/ig, rep.firstName );
      template = template.replaceAll( /\[\[REP_LAST\]\]/ig, rep.lastName );
      template = template.replaceAll( /\[\[REP_TITLE\]\]/ig, rep.title );
      template = template.replaceAll( /\[\[SENDER_FIRST\]\]/ig, sender.firstName );
      template = template.replaceAll( /\[\[SENDER_LAST\]\]/ig, sender.lastName );

      $preview.val( template );

      this._template = value;
    }

    /**
     * Sets whether or not the email is for representatives that are in favor of the campaign.
     *
     * @param {boolean} value
     */
    setFavorable( value ) {
      this._$form[0].for.value = value ? "favored" : "opposed";
      this._favorable = value;
    }

    /**
     * Initializes form event handlers.
     *
     * @param {string} editNonce is the nonce for edit/delete requests.
     */
    _initForm( editNonce ) {

      this._$form[0]._wpnonce.value = editNonce;

      const I = this;

      $( this._$form[0].preview ).on( "scroll", function( evt ) {
        if ( "" === I._scrolling ) {
          I._scrolling = "preview";
        }
        if ( "preview" === I._scrolling ) {
          I._$form[0].template.scrollTo({
            top: evt.target.scrollTop
          });
        }
      });
      $( this._$form[0].template ).on( "scroll", function( evt ) {
        if ( "" === I._scrolling ) {
          I._scrolling = "template";
        }
        if ( "template" === I._scrolling ) {
          I._$form[0].preview.scrollTo({
            top: evt.target.scrollTop
          });
        }
      });
      $( this._$form[0].template ).on( "scrollend", () => I._scrolling = "" );
      $( this._$form[0].preview ).on( "scrollend", () => I._scrolling = "" );

      $( this._$form[0].template ).on( "input", function( evt ) {
        I.setTemplate( evt.target.value );
        I._$form.find( "input[formaction=\"update_email_template\"]" ).first().val( "Update Email (unsaved)" );
        I._$form.find( ".congress-form-error" ).first().text( "" );
      });

      $( this._$form[0].subject ).on( "input", function() {
        I._$form.find( "input[formaction=\"update_email_template\"]" ).first().val( "Update Email (unsaved)" );
        I._$form.find( ".congress-form-error" ).first().text( "" );
      });
      $( this._$form[0].favorable ).on( "input", function() {
        I._$form.find( "input[formaction=\"update_email_template\"]" ).first().val( "Update Email (unsaved)" );
        I._$form.find( ".congress-form-error" ).first().text( "" );
      });

      // handle form submit
      const formData = {
        object: this,
        success: this.handleForm,
        error: this.handleFormError
      };
      this._$form.on( "submit", null, formData, ajaxHandler );

    }

    /**
     * Handles a form submission.
     *
     * @param {object} data is the response data.
     * @param {string} formMethod is the request method.
     */
    handleForm( data, formAction ) {
      if ( "update_email_template" === formAction ) {
        const { campaignID, id, subject, favorable, template } = data;
        this.setID( id, campaignID );
        this.setSubject( subject );
        this.setFavorable( favorable );
        this.setTemplate( template );

        this._$form.find( ".congress-form-error" ).first().text( "" );
        this._$form.find( "input[formaction=\"update_email_template\"]" ).first().val( "Update Email (saved!)" );
      } else if ( "delete_email_template" === formAction ) {
        this._$root.remove();
      }
    }

    /**
     * Handles errors during form submission.
     *
     * @param {string} err is the error.
     * @param {object} response is the request response.
     * @param {string} formMethod is the request methos.
     */
    handleFormError( err, { response, formAction }) {
      if ( "update_email_template" === formAction ) {
        this._$form.find( ".congress-form-error" ).first().text( err );
        this._$form.find( "input[formaction=\"update_email_template\"]" ).first().val( "Update Email (error!)" );
      } else if ( "delete_email_template" === formAction ) {
        if ( response.template ) {
          this._$form.find( ".congress-form-error" ).eq( 1 ).text( err );
        }
      }
    }
  }

  /**
   * Helps initialize html and handle delete events.
   */
  class ArchivedCampaign {

    /**
     * $root is the root of the campaign DOM.
     *
     * @type {HTMLLIElement}
     */
    _$root;

    /**
     * The database id of the campaign.
     *
     * @type {number}
     */
    _id;

    /**
     * The name of the campaign.
     *
     * @type {string}
     */
    _name;

    /**
     * The region of the campaign.
     *
     * @type string
     */
    _region;

    /**
     * The archived date of the campaign.
     *
     * @type Date
     */
    _archivedDate;

    /**
     * The created date of the campaign.
     *
     * @type Date
     */
    _createdDate;

    /**
     * Generates itself from html drawn by the server.
     *
     * @param {jQueryElement} $li is the root of the campaign's DOM.
     * @return {ArchivedCampaign} campaign
     */
    static fromHTML( $li ) {

      const $spans = $li.find( "span" );
      const $titleSpan = $spans.eq( 0 );
      const $emailsSpan = $spans.eq( 1 );
      const $dateSpan = $spans.eq( 2 );

      const titleText = $titleSpan.text();
      let region = titleText.match( /\((.*)\)$/ )[1];
      const name = titleText.replace( ` (${region})`, "" );
      region = region.toLowerCase();
      const numEmails = parseInt( $emailsSpan.text().match( /(\d*) emails sent!/ )[1]);
      const dateSplit = $dateSpan.text().split( " - " );
      const createdDate = new Date( dateSplit[0]);
      const archivedDate = new Date( dateSplit[1]);

      const form = $li.find( ".congress-campaign-delete-form" )[0];
      const id = form.id.value;

      const campaign = new ArchivedCampaign( $li, id, name, region, numEmails, createdDate, archivedDate );
      campaign._initDeleteForm();
      return campaign;
    }

    /**
     * Gets the container that campaign elements are stored in.
     *
     * @returns {HTMLUListElement}
     */
    static getContainer() {
      return $( "#congress-archived-campaigns-container .congress-campaign-list" )[0];
    }

    /**
     * Constructs campaign and adds event listeners.
     *
     * @param {jQueryElement} $root is the root of the Campaign's DOM.
     * @param {number} id is the database id of the campaign.
     * @param {string} name is the name of the campaign.
     * @param string region is the region of the campaign.
     * @param {number} numEmails is the number of emails sent in this campaign.
     * @param {Date} createdDate is the date the campaign was created.
     * @param {Date} archivedDate is the date the campaign was archived.
     */
    constructor( $root, id, name, region, numEmails, createdDate, archivedDate ) {
      this._$root = $root;
      this._id = id;
      this._name = name;
      this._region = region;
      this._numEmails = numEmails;
      this._createdDate = createdDate;
      this._archivedDate = archivedDate;
    }

    /**
     * Helper function to display dates properly.
     *
     * @param date {Date}
     *
     * @returns {string}
     * }
     */
    _displayDate( date ) {
      const dateStr = date.getDate().toString().padStart( 2, "0" );
      const month = String( date.getMonth() + 1 ).padStart( 2, "0" );
      const year = date.getFullYear().toString();
      return `${month}/${dateStr}/${year}`;
    }

    /**
     * Creates a Campaign DOM from the template.
     *
     * @param {string} deleteNonce
     *
     * @returns {HTMLDivElement}
     */
    drawTemplate( deleteNonce ) {
      const template = $( "#congress-archived-campaign-template" )[0];
      const div = template.content.cloneNode( true );

      this._$root.append( div );

      const $spans = this._$root.find( "span" );
      const $titleSpan = $spans.eq( 0 );
      const $emailsSpan = $spans.eq( 1 );
      const $dateSpan = $spans.eq( 2 );

      $titleSpan.text( `${this._name} (${this._region.toProperCase()})` );
      $emailsSpan.text( `${this._numEmails} emails sent!` );
      $dateSpan.text( `${this._displayDate( this._createdDate )} - ${this._displayDate( this._archivedDate )}` );

      const form = this._$root.find( ".congress-campaign-delete-form" )[0];
      form.id.value = this._id;
      form._wpnonce.value = deleteNonce;

      this._initDeleteForm();
    }

    /**
     * Initializes the delete button form.
     */
    _initDeleteForm() {
      const data = {
        object: this,
        success: this.handleDelete,
        error: this.handleDeleteError
      };
      this._$root
        .find( ".congress-campaign-delete-form" )
        .first()
        .on( "submit", null, data, ajaxHandler );
    }

    /**
     * Handles the successful result of a delete request.
     */
    handleDelete() {
      this._$root.remove();
    }

    /**
     * Handles the unsuccessful result of a delete request.
     */
    handleDeleteError( err ) {
      const $form = this._$root.find( ".congress-campaign-delete-form" ).first();
      $form.find( ".congress-form-error" ).text( err );
    }

  }

  /**
   * Used to manage a campaign's email template page.
   */
  class EmailTemplatePage {

    /**
     * The DOM root.
     *
     * @type {JQueryDivElement}
     */
    _$root;

    /**
     * The id of the page's campaign.
     *
     * @type {number}
     */
    _campaignID;

    /**
     * The nonce to load email templates.
     *
     * @type {string}
     */
    _nonce;

    /**
     * Track load status.
     *
     * @type {boolean}
     */
    _isLoaded;

    /**
     * The delete form.
     *
     * @type {HTMLFormElement}
     */
    _deleteForm;

    /**
     * The create form.
     *
     * @type {HTMLFormElement}
     */
    _createForm;

    /**
     * The csv form.
     *
     * @type {HTMLFormElement}
     */
    _csvForm;

    /**
     * The container for email templates.
     *
     * @type {QueryULElement}
     */
    _$container;

    /**
     * Initializes the page.
     *
     * Note: @see load must be called to view email templates.
     *
     * @param campaignID {number} is the page's campaign id.
     * @param $root {JQueryDivElement} is the root of the page.
     */
    constructor( campaignID, $root ) {
      const createForm = $root.find( ".congress-campaign-email-create-form" )[0];
      createForm.campaign_id.value = campaignID;
      this._createForm = createForm;

      const csvForm = $root.find( ".congress-campaign-email-upload-csv-form" )[0];
      csvForm.campaign_id.value = campaignID;
      this._csvForm = csvForm;

      const deleteForm = $root.find( ".congress-campaign-email-delete-all-form" )[0];
      deleteForm.campaign_id.value = campaignID;
      this._deleteForm = deleteForm;

      this._$container = $root.find( ".congress-campaign-email-list" ).first();

      this._$root = $root;
      this._campaignID = campaignID;
      this._isLoaded = false;
    }

    /**
     * Campaign ID setter.
     *
     * @param {number} campaignID
     */
    setCampaignID( campaignID ) {
      this._createForm.campaign_id.value = campaignID;
      this._csvForm.campaign_id.value = campaignID;
      this._deleteForm.campaign_id.value = campaignID;
      this._campaignID = campaignID;
    }

    /**
     * Set the nonce to load the page.
     *
     * @param {string} nonce
     */
    setNonce( nonce ) {
      this._nonce = nonce;
    }

    /**
     * Has the page been loaded?
     *
     * @return {boolean}
     */
    isLoaded() {
      return this._isLoaded;
    }

    /**
     * Load email templates into the page, and add nonces.
     *
     * You should use @see isLoaded to determine if you need to call this.
     * You should use @see setNonce before using this method if the page is
     * from a campaign created during the browser session.
     */
    load( callback ) {

      if ( undefined === this._nonce ) {

        // attempt to find nonce in ajax object passed by wordpress
        const nonce = congressAjaxObj?.loadTemplateNonce[this._campaignID];

        if ( ! nonce ) {
          throw "Nonce not set";
        }

        this._nonce = nonce;
      }

      const I = this;
      $.post(
        ajaxurl,
        {
          "action": "load_email_templates",
          "campaign_id": this._campaignID,
          "_wpnonce": this._nonce
        },
        function({createNonce, csvNonce, deleteAllNonce, templates}) {
          I._createForm._wpnonce.value = createNonce;
          $( I._createForm ).on(
            "submit",
            null,
            {
              object: I,
              success: I.handleCreate,
              error: I.handleCreateError
            },
            ajaxHandler
          );

          I._csvForm._wpnonce.value = csvNonce;
          $( I._csvForm ).on(
            "submit",
            null,
            {
              object: I,
              success: I.handleCSV,
              error: I.handleCSVError
            },
            ajaxUploadHandler
          );

          I._deleteForm._wpnonce.value = deleteAllNonce;
          $( I._deleteForm ).on(
            "submit",
            null,
            {
              object: I,
              success: I.handleDelete,
              error: I.handleDeleteError
            },
            ajaxHandler
          );

          I._addTemplates( templates );
          I._isLoaded = true;

          callback();
        }
      );

    }

    /**
     * Adds templates to the DOM.
     *
     * @param {Array<{campaignID: number, id: number, subject: string, favorable: boolean, template: string, editNonce: string}>} templates
     */
    _addTemplates( templates ) {
      for ( let data of templates ) {
        this._addTemplate( data );
      }
    }

    /**
     * Adds a template to the DOM.
     *
     * @param {number} campaignID
     * @param {number} id
     * @param {string} subject
     * @param {boolean} favorable
     * @param {string} template
     * @param {string} editNonce
     */
    _addTemplate({ campaignID, id, subject, favorable, template, editNonce }) {
      if ( "string" === typeof favorable ) {
        favorable = ( 0 !== parseInt( favorable ) );
      }
      const templateObj = new EmailTemplate( campaignID, id, subject, favorable, template );
      const $li = $( "<li>" )
        .prependTo( this._$container );
      templateObj.createDOM( $li, editNonce );
    }

    /**
     * Handles a successful request to create an email template.
     *
     * @param {{campaignID: number, id: number, subject: string, favorable: boolean, template: string, editNonce: string}} template
     */
    handleCreate( template ) {
      this._addTemplate( template );
      $( this._createForm ).find( ".congress-form-error" ).text( "" );
    }

    /**
     * Handles a failed request to create an email template.
     *
     * @param {string} err
     */
    handleCreateError( err ) {
      $( this._createForm ).find( ".congress-form-error" ).text( err );
    }

    /**
     * Handles a successful request to create templates from a CSV.
     *
     * @param {Array} @see handleCreate for the type.
     */
    handleCSV( templates ) {
      this._addTemplates( templates );
      $( this._csvForm ).find( ".congress-form-error" ).text( "" );
    }

    /**
     * Handles a failed request to create email templates from a CSV.
     *
     * @param {string} err
     */
    handleCSVError( err ) {
      $( this._csvForm ).find( ".congress-form-error" ).text( err );
    }

    /**
     * Handles a successful request to delete an email template.
     */
    handleDelete() {
      this._$container.empty();
      $( this._deleteForm ).find( ".congress-form-error" ).text( "" );
    }

    /**
     * Handles a failed request to delete an email template.
     */
    handleDeleteError( err ) {
      $( this._deleteForm ).find( ".congress-form-error" ).text( err );
    }
  }


  /**
   * The ActiveCampaign class helps initialize html and event handlers, and it
   * also helps carry state across different events.
   */
  class ActiveCampaign {

    /**
     * Generates an ActiveCampaign from html drawn by the server.
     *
     * @param {jQueryElement} $li is the root of the campaign's DOM.
     * @return {ActiveCampaign} campaign
     */
    static fromHTML( $li ) {
      const id = $li[0].id.match( /congress-campaign-(\d*)/ )[1];
      const form = $li.find( ".congress-campaign-edit-form" )[0];
      const name = form.name.value;
      const region = form.region.value;
      const campaign = new ActiveCampaign( id, name, region, $li );
      campaign.changePage( "edit" );
      return campaign;
    }

    /**
     * Generates a campaign from the response of an Ajax create campaign request.
     *
     * @param {number} id  is the id of the campaign.
     * @param {string} name  is the name of the campaign.
     * @param {string} region  is the region of the campaign.
     * @param {string} regionDisplay  is the fullname, properly capitalized, region of the campaign.
     * @param {string} editNonce  is the nonce used for the edit form.
     * @param {string} archiveNonce  is the nonce used for the archive button.
     * @param {string} templateLoadNonce  is the nonce used for loading the email templates page.
     *
     * @returns {ActiveCampaign}
     */
    static fromCreateRequest({id, name, region, regionDisplay, editNonce, archiveNonce, templateLoadNonce }) {
      const template = ActiveCampaign.createTemplate();
      const container = ActiveCampaign.getContainer();
      const li = document.createElement( "li" );
      li.append( template );
      container.prepend( li );

      const campaign = new ActiveCampaign( -1, "", "", $( li ) );
      campaign.toggleExpansion( false );
      campaign.setID( id );
      campaign.setCampaignData( name, region, regionDisplay );
      campaign.updateEditNonce( editNonce );
      campaign.updateArchiveNonce( archiveNonce );
      campaign.emailTemplatePage.setNonce( templateLoadNonce );
      campaign.changePage( "templates" );

      return campaign;
    }

    /**
     * Gets the container that campaign elements are stored in.
     *
     * @returns {HTMLUListElement}
     */
    static getContainer() {
      return $( "#congress-active-campaigns-container .congress-campaign-list" )[0];
    }

    /**
     * Creates a Campaign DOM from the template.
     *
     * @returns {HTMLDivElement}
     */
    static createTemplate() {
      const template = $( "#congress-active-campaign-template" )[0];
      return template.content.cloneNode( true );
    }

    /**
     * A helper function to call methods from an event.
     *
     * @param {jQueryEvent} evt should have data fields:
     *  - campaign {Campaign}
     *  - func {CampaignFunction}
     *  - args {array}
     */
    static _handleEvent( evt ) {
      evt.preventDefault();
      const campaign = evt.data.campaign;
      const func = evt.data.func;
      const args = evt.data.args;
      campaign[func.name]( ...args );
    };

    /**
     * $root is the root of the Campaign DOM.
     *
     * @type {HTMLLIElement}
     */
    _$root;

    /**
     * The current page of the campaign.
     *
     * @type {string}
     */
    _currentPage;

    /**
     * An object that maps page names to the html links.
     *
     * The page name values are based on the html class
     * congress-campaign-{id}-{pageName}-page.
     *
     * @type {Object<string,jQueryAnchorElement>}
     */
    _$pageLinks = {};

    /**
     * A reference to the toggle button used for expanding and
     * collapsing the campaign.
     *
     * @type {jQueryButtonElement}
     */
    _$expandToggle;

    /**
     * A reference to the body of the campaign that can be expanded or collapsed.
     *
     * @type {jQueryDivElement}
     */
    _$campaignBody;

    /**
     * The database id of the campaign.
     *
     * Setting this variable must be done through @see setID
     * The only exception is the constructor.
     *
     * @type {number}
     */
    _id;

    /**
     * The name of the campaign.
     *
     * Setting this variable must be done through @see setCampaignData
     * The only exception is the constructor.
     *
     * @type {string}
     */
    _name;

    /**
     * The region of the campaign.
     *
     * Setting this variable must be done through @see setCampaignData.
     * The only exception is the constructor.
     *
     * @type string
     */
    _region;

    /**
     * The email template page.
     *
     * @type {EmailTemplatePage}
     */
    emailTemplatePage;

    /**
     * Constructs a Campaign and adds event listeners.
     *
     * @param {number} id is the database id of the campaign.
     * @param {string} name is the name of the campaign.
     * @param {string} region is the region of the campaign.
     * @param {jQueryElement} $root is the root of the Campaign's DOM.
     */
    constructor( id, name, region, $root ) {

      this._id = id;
      this._name = name;
      this._region = region;
      this._$root = $root;
      this._initPageLinks();
      this._initExpansionToggle();
      this._initEditForm();
      this._initArchiveForm();
      this.emailTemplatePage = new EmailTemplatePage( id, $( this._$pageLinks.templates.attr( "href" ) ) );

    }

    /**
     * Sets the campaign id.
     *
     * @param {number} id
     */
    setID( id ) {

      /*
       * The id is used extensively in the HTML:
       * - hidden form fields
       * - form labels' for attribute and input ids
       * - page href attributes
       */

      const idPlaceholder = "campaign_id";

      // edit form
      const editForm = this._$root.find( ".congress-campaign-edit-form" )[0];
      $( editForm ).find( "label" ).each( function() {
        const oldID = $( this ).attr( "for" );
        const field = $( "#" + oldID )[0];
        const newID = oldID.replace( idPlaceholder, id );
        field.id = newID;
        $( this ).attr( "for", newID );
      });
      editForm.name.id = editForm.name.id.replace( idPlaceholder, id );
      editForm.region.id = editForm.region.id.replace( idPlaceholder, id );
      editForm.id.value = id;

      // archive form
      const arciveForm = this._$root.find( ".congress-campaign-archive-form" )[0];
      arciveForm.id.value = id;

      // pages
      for ( const $link of Object.values( this._$pageLinks ) ) {
        const pageID = $link.attr( "href" ).slice( 1 );
        const newPageID = pageID.replace( idPlaceholder, id );
        $link.attr( "href", "#" + newPageID );
        $( "#" + pageID )[0].id = newPageID;
      }

      this.emailTemplatePage.setCampaignID( id );

      this._id = id;
    }

    /**
     * Gets the campaign id.
     *
     * @return number
     */
    getID() {
      return this._id;
    }

    /**
     * Initializes that no pages are selected, and adds event handlers.
     */
    _initPageLinks() {
      const $pageLinks = this._$root.find( ".congress-nav" ).first().children( "li" );
      const I = this;
      $pageLinks.each( function() {
        const $childLI = $( this ).children( "a" ).first();
        if ( 0 === $childLI.length ) {
          return;
        }
        const $pageLink = $childLI.first();
        const href = $pageLink.attr( "href" );
        const name = href.match( /#congress-campaign-([^-]*)-([A-z]*)-page/ )[2];

        I._$pageLinks[name] = $pageLink;
        $( href ).toggleClass( "congress-hidden", true );
        $pageLink.toggleClass( "congress-active", false );

        const data = {
          campaign: I,
          func: I.changePage,
          args: [ name ]
        };
        $pageLink.on( "click", null, data, ActiveCampaign._handleEvent );
      });
    }

    /**
     * Initializes the state of toggle for expanding/collapsing the campaign
     * and adds event handlers.
     */
    _initExpansionToggle() {
      this._$expandToggle = this._$root.find( ".congress-campaign-toggle" ).first();
      this._$campaignBody = this._$root.find( ".congress-card-body" ).first();
      const isHidden = this._$campaignBody.hasClass( "congress-hidden" );

      if ( isHidden ) {
        this._$expandToggle
          .empty()
          .append( document.createTextNode( "More" ) );

        $( "<span>" )
          .addClass( "congress-inline-dashicon dashicons-plus-alt2" )
          .appendTo( this._$expandToggle );
      } else {
        this._$expandToggle
          .empty()
          .append( document.createTextNode( "Less" ) );

        $( "<span>" )
          .addClass( "congress-inline-dashicon dashicons-minus" )
          .appendTo( this._$expandToggle );
      }

      const data = {
        campaign: this,
        func: this.toggleExpansion,
        args: []
      };
      this._$expandToggle.on( "click", null, data, ActiveCampaign._handleEvent );

    }

    /**
     * Sets campaign data.
     *
     * This should be used instead of setting @see _name and @see _region manually.
     * If campaign data and id need set at the same time, use @see setID first.
     *
     * Used when creating/editing.
     */
    setCampaignData( name, region, regionDisplay ) {

      // header
      this._$root
        .find( ".congress-card-header > span" )
        .first()
        .text( `${name} (${regionDisplay})` );

      // edit form
      const form = this._$root.find( ".congress-campaign-edit-form" )[0];
      form.name.value = name;
      form.region.value = region;
      form.id.value = this._id;

      this._name = name;
      this._region = region;
    }

    /**
     * Updates the edit form nonce.
     */
    updateEditNonce( editNonce ) {
      const form = this._$root.find( ".congress-campaign-edit-form" )[0];
      if ( editNonce ) {
        form._wpnonce.value = editNonce;
      }
    };

    /**
     * Initializes the event listener for the campaign edit form.
     */
    _initEditForm() {
      const data = {
        object: this,
        success: this.handleEdit,
        error: this.handleEditError
      };
      this._$root
        .find( ".congress-campaign-edit-form" )
        .first()
        .on( "submit", null, data, ajaxHandler );
    };

    /**
     * Handles an the Ajax edit campaign response.
     *
     * @param {string} name
     * @param {string} region
     * @param {string} regionDisplay  is the fullname, properly capitalized, region of the campaign.
     */
    handleEdit({ name, region, regionDisplay }) {
      this.setCampaignData( name, region, regionDisplay );
      const $form = this._$root.find( ".congress-campaign-edit-form" ).first();
      $form.find( ".congress-form-error" ).text( "" );
    }

    /**
     * Handles errors from the edit campaign Ajax request.
     *
     * @param {string} err
     */
    handleEditError( err ) {
      const $form = this._$root.find( ".congress-campaign-edit-form" ).first();
      $form.find( ".congress-form-error" ).text( err );
    }


    /**
     * Updates the archive form nonce.
     */
    updateArchiveNonce( archiveNonce ) {
      const form = this._$root.find( ".congress-campaign-archive-form" )[0];
      if ( archiveNonce ) {
        form._wpnonce.value = archiveNonce;
      }
    };

    /**
     * Initializes the event listener for the campaign archive form.
     */
    _initArchiveForm() {
      const data = {
        object: this,
        success: this.handleArchive,
        error: this.handleArchiveError
      };
      this._$root
        .find( ".congress-campaign-archive-form" )
        .first()
        .on( "submit", null, data, ajaxHandler );
    };

    /**
     * Handles an the Ajax archive campaign response.
     *
     * @param {} createdDate
     * @param {} archivedDate
     * @param {string} deleteNonce
     */
    handleArchive({ createdDate, emailCount, archivedDate, deleteNonce }) {
      this._$root.remove();
      const $container = $( ArchivedCampaign.getContainer() );
      const $root = $( "<li>" );
      $container.prepend( $root );
      const campaign = new ArchivedCampaign(
        $root,
        this._id,
        this._name,
        this._region,
        emailCount,
        new Date( createdDate.replaceAll( "-", "/" ) ),
        new Date( archivedDate.replaceAll( "-", "/" ) )
      );
      campaign.drawTemplate( deleteNonce );
    }

    /**
     * Handles errors from the archive campaign Ajax request.
     *
     * @param {string} err
     */
    handleArchiveError( err ) {
      const $form = this._$root.find( ".congress-campaign-archive-form" ).first();
      $form.find( ".congress-form-error" ).text( err );
    }

    /**
     * Toggles whether the campaign is expanded or collapsed.
     *
     * @param {boolean|null} isHiddenState will cause this function to
     * set the state instead of toggling.
     */
    toggleExpansion( isHiddenState = null ) {
      let isHidden;
      if ( null !== isHiddenState ) {
        isHidden = isHiddenState;
      } else {
        isHidden = ! this._$campaignBody.hasClass( "congress-hidden" );
      }

      this._$campaignBody.toggleClass( "congress-hidden", isHidden );
      if ( isHidden ) {
        this._$expandToggle
          .empty()
          .append( document.createTextNode( "More" ) );

        $( "<span>" )
          .addClass( "congress-inline-dashicon dashicons-plus-alt2" )
          .appendTo( this._$expandToggle );
      } else {
        this._$expandToggle
          .empty()
          .append( document.createTextNode( "Less" ) );

        $( "<span>" )
          .addClass( "congress-inline-dashicon dashicons-minus" )
          .appendTo( this._$expandToggle );
      }

      this.scrollTo();
    }

    /**
     * Changes the page to pageName.
     *
     * pageName's possible values are based on the html class
     * congress-campaign-{id}-{pageName}-page.
     *
     * @param {string} pageName
     */
    changePage( pageName ) {

      if ( this._currentPage ) {
        const $oldPageLink = this._$pageLinks[this._currentPage];
        const $oldPageBody = $( $oldPageLink.attr( "href" ) );
        $oldPageLink.toggleClass( "congress-active", false );
        $oldPageBody.toggleClass( "congress-hidden", true );
      }

      const I = this;
      const scrollFunc = function() {
        I.scrollTo();
      };

      this._currentPage = pageName;

      const $pageLink = this._$pageLinks[pageName];
      const $pageBody = $( $pageLink.attr( "href" ) );
      $pageLink.toggleClass( "congress-active", true );
      $pageBody.toggleClass( "congress-hidden", false );

      if ( "templates" === pageName ) {
        if ( ! this.emailTemplatePage.isLoaded() ) {
          this.emailTemplatePage.load( scrollFunc );
        }
      } else {
        this.scrollTo();
      }


    }

    /**
     * Scrolls so the the campaign is in center view.
     */
    scrollTo() {
      scrollTo({
        top: this._$root[0].offsetTop - ( ( document.body.clientHeight - this._$root[0].offsetHeight ) / 2 ),
        behavior: "instant"
      });

    }
  }

  /**
   * Handles switching the page to show 'archived' or 'active' campaigns.
   *
   * @param evt {jQueryEvent} A jQuery event with a data object containing the $dropdown, $archiveContainer, and $activeContainer.
   */
  function onArchiveToggle( evt ) {
    const $dropdown = evt.data.$dropdown;
    const $archiveContainer = evt.data.$archiveContainer;
    const $activeContainer = evt.data.$activeContainer;

    if ( "active" === $dropdown[0].value ) {
      $activeContainer.toggleClass( "congress-hidden", false );
      $archiveContainer.toggleClass( "congress-hidden", true );
    } else {
      $activeContainer.toggleClass( "congress-hidden", true );
      $archiveContainer.toggleClass( "congress-hidden", false );
    }
  }

  /**
   * Displays the current page, hides the other page, and adds an event listener.
   */
  function initArchiveToggle() {
    const $dropdown = $( "#congress-campaign-archive-toggle" ).first();
    const $activeContainer = $( "#congress-active-campaigns-container" ).first();
    const $archiveContainer = $( "#congress-archived-campaigns-container" ).first();
    const data = {
      $dropdown,
      $activeContainer,
      $archiveContainer
    };

    if ( "active" === $dropdown[0].value ) {
      $activeContainer.toggleClass( "congress-hidden", false );
      $archiveContainer.toggleClass( "congress-hidden", true );
    } else {
      $activeContainer.toggleClass( "congress-hidden", true );
      $archiveContainer.toggleClass( "congress-hidden", false );
    }

    $dropdown.on( "input", null, data, onArchiveToggle );

  }

  /**
   * Initializes the add campaign button.
   *
   * @param {SubmitEvent} evt
   */
  function addCampaign( evt ) {
    evt.preventDefault();
    ActiveCampaign.fromCreateRequest( new FormData( evt.target ) );
  }

  $( () => {
    $( "#congress-active-campaigns-container > .congress-campaign-list > li" ).each( function() {
      ActiveCampaign.fromHTML( $( this ) );
    });
    $( "#congress-archived-campaigns-container > .congress-campaign-list > li" ).each( function() {
      ArchivedCampaign.fromHTML( $( this ) );
    });
    initArchiveToggle();

    const object = {
      addCampaign: ( data ) => {
        ActiveCampaign.fromCreateRequest( data );
        $( "#congress-campaign-add-error" ).first().text( "" );
      },
      addCompaignFailed: ( err ) => {
        $( "#congress-campaign-add-error" ).first().text( err );
      }
    };
    const data = {
      object: object,
      success: object.addCampaign,
      error: object.addCompaignFailed
    };
    $( "#congress-campaign-add" ).first().on( "submit", null, data, ajaxHandler );

  });

}( jQuery ) );
