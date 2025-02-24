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
   * Holds state for campaign pages.
   *
   * Key is the campaignID, and the value is an obect containing the current page's body and link ($page, $link).
   */
  const currentPages = {};

  /**
   * Handles switching the page in a campaign.
   *
   * @param evt {jQueryEvent} A jQuery event with a data object containing the campaign $page, $link, and campaignID.
   */
  function switchCampaignPage( evt ) {
    evt.data.$page.toggleClass( "congress-hidden" );
    evt.data.$link.toggleClass( "congress-active" );
    currentPages[ evt.data.campaignID ].$page.toggleClass( "congress-hidden" );
    currentPages[ evt.data.campaignID ].$link.toggleClass( "congress-active" );
    currentPages[ evt.data.campaignID ] = {
      $page: evt.data.$page,
      $link: evt.data.$link
    };
  }

  /**
   * Sets the page for the campaign, and adds event listeners for page switches.
   *
   * @param {HTMLLIElement} li is the campaign list element.
   */
  function initCampaignPage( li ) {

      const campaignID = parseInt( li.id.match( /congress-campaign-(\d*)/ )[1]);
      const $pages = $( li )
        .find( ".congress-campaign-pages-container" )
        .first()
        .children( ".congress-campaign-page-container" );
      const $links = $( li )
        .find( ".congress-nav" )
        .first()
        .find( "a" );

      if ( $pages.length != $links.length ) {
        throw "There is not 1 link for every campaign page!";
      }

      for ( let i = 0; i < $pages.length; i++ ) {
        const $page = $( $pages[i]);
        const $link = $( $links[i]);
        const data = {
          campaignID: campaignID,
          $page: $page,
          $link: $link
        };

        $link.on( "click", null, data, switchCampaignPage );

        const isCurrentPage = 0 === i;
        if ( isCurrentPage ) {
          currentPages[campaignID] = {
            $page: $page,
            $link: $link
          };
        }
        if ( ! isCurrentPage && ! $page.hasClass( "congress-hidden" ) ) {
          $page.addClass( "congress-hidden" );
        } else if ( isCurrentPage && $page.hasClass( "congress-hidden" ) ) {
          $page.removeClass( "congress-hidden" );
        }
        if ( ! isCurrentPage && $link.hasClass( "congress-active" ) ) {
          $link.removeClass( "congress-active" );
        } else if ( isCurrentPage && ! $link.hasClass( "congress-active" ) ) {
          $link.addClass( "congress-active" );
        }

      }
  }

  /**
   * Handles toggling a campaign to expand/collapse.
   *
   * @param evt {jQueryEvent} A jQuery event with a data object containing the campaign $toggle and $body.
   */
  function onCampaignExpandToggle( evt ) {
    const $toggle = evt.data.$toggle;
    const $body = evt.data.$body;
    const isHidden = ! $body.hasClass( "congress-hidden" );

    $body.toggleClass( "congress-hidden", isHidden );
    if ( isHidden ) {
      $toggle.text( "More >" );
    } else {
      $toggle.text( "Less ^" );
    }

  }

  /**
   * Collapses the campaign, and add an event listener.
   *
   * @param {HTMLLIElement} li is the campaign list element.
   */
  function initCampaignExpand( li ) {

    const $toggle = $( li ).find( ".congress-campaign-toggle" ).first();
    const $body = $( li ).find( ".congress-card-body" ).first();
    const data = {
      $toggle: $toggle,
      $body: $body
    };
    const isHidden = $body.hasClass( "congress-hidden" );

    if ( isHidden ) {
      $toggle.text( "More >" );
    } else {
      $toggle.text( "Less ^" );
    }
    $toggle.on( "click", null, data, onCampaignExpandToggle );

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

  $( () => {
    $( "#congress-active-campaigns-container > .congress-campaign-list > li" ).each( function() {
      initCampaignPage( this );
      initCampaignExpand( this );
    });
    initArchiveToggle();

  });

}( jQuery ) );
