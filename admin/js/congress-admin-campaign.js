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
    const formMethod = form.attributes?.method?.nodeValue || "get";

    const object = evt.data.object;
    const success = evt.data.success;
    const error = evt.data.error;

    const body = {
      action: form.attributes.action.nodeValue
    };
    for ( const pair of formData.entries() ) {
      body[pair[0]] = pair[1];
    }
    $[formMethod](
      ajaxurl,
      body,
      ( data ) => object[success.name]( data )
    ).fail( ( err ) => object[error.name]( err.responseJSON.error ) );
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
     * The level of the campaign.
     *
     * @type {'federal' | 'state'}
     */
    _level;

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
      let level = titleText.match( /\((.*)\)$/ )[1];
      const name = titleText.replace( ` (${level})`, "" );
      level = level.toLowerCase();
      const numEmails = parseInt( $emailsSpan.text().match( /(\d*) emails sent!/ )[1]);
      const dateSplit = $dateSpan.text().split( " - " );
      const createdDate = new Date( dateSplit[0]);
      const archivedDate = new Date( dateSplit[1]);

      const form = $li.find( ".congress-campaign-delete-form" )[0];
      const id = form.id.value;

      const campaign = new ArchivedCampaign( $li, id, name, level, numEmails, createdDate, archivedDate );
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
     * @param {'federal'|'state'} level is the level of the campaign.
     * @param {number} numEmails is the number of emails sent in this campaign.
     * @param {Date} createdDate is the date the campaign was created.
     * @param {Date} archivedDate is the date the campaign was archived.
     */
    constructor( $root, id, name, level, numEmails, createdDate, archivedDate ) {
      this._$root = $root;
      this._id = id;
      this._name = name;
      this._level = level;
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

      $titleSpan.text( `${this._name} (${this._level.toProperCase()})` );
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
      const level = form.level.value;
      const campaign = new ActiveCampaign( id, name, level, $li );
      campaign.changePage( "edit" );
      return campaign;
    }

    /**
     * Generates a campaign from the response of an Ajax create campaign request.
     *
     * @param {number} id  is the id of the campaign.
     * @param {number} name  is the name of the campaign.
     * @param {number} level  is the level of the campaign.
     * @param {number} editNonce  is the nonce used for the edit form.
     * @param {number} archiveNonce  is the nonce used for the archive button.
     *
     * @returns {ActiveCampaign}
     */
    static fromCreateRequest({id, name, level, editNonce, archiveNonce }) {
      const template = ActiveCampaign.createTemplate();
      const container = ActiveCampaign.getContainer();
      const li = document.createElement( "li" );
      li.append( template );
      container.prepend( li );

      const campaign = new ActiveCampaign( -1, "", "", $( li ) );
      campaign.changePage( "templates" );
      campaign.toggleExpansion( false );
      campaign.setID( id );
      campaign.setCampaignData( name, level );
      campaign.updateEditNonce( editNonce );
      campaign.updateArchiveNonce( archiveNonce );

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
     * The level of the campaign.
     *
     * Setting this variable must be done through @see setCampaignData.
     * The only exception is the constructor.
     *
     * @type {'federal' | 'state'}
     */
    _level;

    /**
     * Constructs a Campaign and adds event listeners.
     *
     * @param {number} id is the database id of the campaign.
     * @param {string} name is the name of the campaign.
     * @param {'federal'|'state'} level is the level of the campaign.
     * @param {jQueryElement} $root is the root of the Campaign's DOM.
     */
    constructor( id, name, level, $root ) {

      this._id = id;
      this._name = name;
      this._level = level;
      this._$root = $root;
      this._initPageLinks();
      this._initExpansionToggle();
      this._initEditForm();
      this._initArchiveForm();

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
      editForm.level.id = editForm.level.id.replace( idPlaceholder, id );
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
        this._$expandToggle.text( "More >" );
      } else {
        this._$expandToggle.text( "Less ^" );
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
     * This should be used instead of setting @see _name and @see _level manually.
     * If campaign data and id need set at the same time, use @see setID first.
     *
     * Used when creating/editing.
     */
    setCampaignData( name, level ) {

      // header
      this._$root
        .find( ".congress-card-header > span" )
        .first()
        .text( `${name} (${level.toProperCase()})` );

      // edit form
      const form = this._$root.find( ".congress-campaign-edit-form" )[0];
      form.name.value = name;
      form.level.value = level;
      form.id.value = this._id;

      this._name = name;
      this._level = level;
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
     * @param {'federal'|'state'} level
     */
    handleEdit({ name, level }) {
      this._name = name;
      this._level = level;
      this.setHeader();
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
        this._level,
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
        this._$expandToggle.text( "More >" );
      } else {
        this._$expandToggle.text( "Less ^" );
      }
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

      this._currentPage = pageName;

      const $pageLink = this._$pageLinks[pageName];
      const $pageBody = $( $pageLink.attr( "href" ) );
      $pageLink.toggleClass( "congress-active", true );
      $pageBody.toggleClass( "congress-hidden", false );
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
