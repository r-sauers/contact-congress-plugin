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
   * The Campaign class helps initialize html and event handlers, and it
   * also helps carry state across different events.
   */
  class Campaign {

    /**
     * Generates a Campaign from html drawn by the server.
     *
     * @param {jQueryElement} $li is the root of the campaign's DOM.
     * @return {Campaign} campaign
     */
    static fromHTML( $li ) {
      const id = $li[0].id.match( /congress-campaign-(\d*)/ )[1];
      const form = $li.find( ".congress-campaign-edit-form" )[0];
      const name = form.name.value;
      const level = form.level.value;
      const campaign = new Campaign( id, name, level, $li );
      campaign.changePage( "edit" );
      return campaign;
    }

    /**
     * Generates a Campaign by sending a create request to the server.
     *
     * @param {FormData} formData is the form data for the create campaign form.
     *
     * @returns {Promise<Campaign>}
     */
    static fromCreateRequest( formData ) {
      return new Promise( ( res ) => {
        $.post(
          ajaxurl,
          {
            action: "add_campaign",
            name: formData.get( "name" ),
            level: formData.get( "level" ),
            _wpnonce: formData.get( "_wpnonce" )
          },
          function({ id, editNonce, archiveNonce }) {
            const template = Campaign.createTemplate();
            const container = Campaign.getContainer();
            const li = document.createElement( "li" );
            li.append( template );
            container.prepend( li );

            const campaign = new Campaign( id, formData.get( "name" ), formData.get( "level" ), $( li ) );
            campaign.changePage( "templates" );
            campaign.toggleExpansion( false );
            campaign.setHeader();

            res( campaign );

          }
        );
      });
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
      const template = $( "#congress-campaign-template" )[0];
      return template.content.cloneNode( true );
    }

    /**
     * $root is the root of the Campaign DOM.
     *
     * @type {HTMLLIElement}
     */
    $root;

    /**
     * The current page of the campaign.
     *
     * @type {string}
     */
    currentPage;

    /**
     * An object that maps page names to the html links.
     *
     * The page name values are based on the html class
     * congress-campaign-{id}-{pageName}-page.
     *
     * @type {Object<string,jQueryAnchorElement>}
     */
    $pageLinks = {};

    /**
     * A reference to the toggle button used for expanding and
     * collapsing the campaign.
     *
     * @type {jQueryButtonElement}
     */
    $expandToggle;

    /**
     * A reference to the body of the campaign that can be expanded or collapsed.
     *
     * @type {jQueryDivElement}
     */
    $campaignBody;

    /**
     * The database id of the campaign.
     *
     * @type {number}
     */
    id;

    /**
     * The name of the campaign.
     *
     * @type {string}
     */
    name;

    /**
     * The level of the campaign.
     *
     * @type {'federal' | 'state'}
     */
    level;

    /**
     * Constructs a Campaign and adds event listeners.
     *
     * @param {number} id is the database id of the campaign.
     * @param {string} name is the name of the campaign.
     * @param {'federal'|'state'} level is the level of the campaign.
     * @param {jQueryElement} $root is the root of the Campaign's DOM.
     */
    constructor( id, name, level, $root ) {

      this.id = id;
      this.name = name;
      this.level = level;
      this.$root = $root;
      this._initPageLinks();
      this._initExpansionToggle();

    }

    /**
     * Initializes that no pages are selected, and adds event handlers.
     */
    _initPageLinks() {
      const $pageLinks = this.$root.find( ".congress-nav" ).first().children( "li" );
      const I = this;
      $pageLinks.each( function() {
        const $childLI = $( this ).children( "a" ).first();
        if ( 0 === $childLI.length ) {
          return;
        }
        const $pageLink = $childLI.first();
        const href = $pageLink.attr( "href" );
        const name = href.match( /#congress-campaign-(\d*)-([A-z]*)-page/ )[2];

        I.$pageLinks[name] = $pageLink;
        $( href ).toggleClass( "congress-hidden", true );
        $pageLink.toggleClass( "congress-active", false );

        const data = {
          self: I,
          func: I.changePage,
          args: [ name ]
        };
        $pageLink.on( "click", null, data, I._handleEvent );
      });
    }

    /**
     * Initializes the state of toggle for expanding/collapsing the campaign
     * and adds event handlers.
     */
    _initExpansionToggle() {
      this.$expandToggle = this.$root.find( ".congress-campaign-toggle" ).first();
      this.$campaignBody = this.$root.find( ".congress-card-body" ).first();
      const isHidden = this.$campaignBody.hasClass( "congress-hidden" );

      if ( isHidden ) {
        this.$expandToggle.text( "More >" );
      } else {
        this.$expandToggle.text( "Less ^" );
      }
      const data = {
        self: this,
        func: this.toggleExpansion,
        args: []
      };
      this.$expandToggle.on( "click", null, data, this._handleEvent );

    }

    /**
     * Sets the header text of the campaign's header.
     *
     * Used when creating/editing.
     */
    setHeader() {
      this.$root
        .find( ".congress-card-header > span" )
        .first()
        .text( `${this.name} (${this.level.toProperCase()})` );
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
        isHidden = ! this.$campaignBody.hasClass( "congress-hidden" );
      }

      this.$campaignBody.toggleClass( "congress-hidden", isHidden );
      if ( isHidden ) {
        this.$expandToggle.text( "More >" );
      } else {
        this.$expandToggle.text( "Less ^" );
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

      if ( this.currentPage ) {
        const $oldPageLink = this.$pageLinks[this.currentPage];
        const $oldPageBody = $( $oldPageLink.attr( "href" ) );
        $oldPageLink.toggleClass( "congress-active", false );
        $oldPageBody.toggleClass( "congress-hidden", true );
      }

      this.currentPage = pageName;

      const $pageLink = this.$pageLinks[pageName];
      const $pageBody = $( $pageLink.attr( "href" ) );
      $pageLink.toggleClass( "congress-active", true );
      $pageBody.toggleClass( "congress-hidden", false );
    };

    /**
     * A helper function to call methods from an event.
     *
     * @param {jQueryEvent} evt should have data fields:
     *  - self {Campaign}
     *  - func {CampaignFunction}
     *  - args {array}
     */
    _handleEvent( evt ) {
      evt.preventDefault();
      const self = evt.data.self;
      const func = evt.data.func;
      const args = evt.data.args;
      self[func.name]( ...args );
    };

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
    Campaign.fromCreateRequest( new FormData( evt.target ) );
  }

  $( () => {
    $( "#congress-active-campaigns-container > .congress-campaign-list > li" ).each( function() {
      Campaign.fromHTML( $( this ) );
    });
    initArchiveToggle();

    $( "#congress-campaign-add" ).first().on( "submit", addCampaign );

  });

}( jQuery ) );
