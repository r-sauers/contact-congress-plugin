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

  const currentPages = {};

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

  $( () => {
    $( "#congress-active-campaigns-container > .congress-campaign-list > li" ).each( function() {
      const campaignID = parseInt( this.id.match( /congress-campaign-(\d*)/ )[1]);
      const $pages = $( this )
        .find( ".congress-campaign-pages-container" )
        .first()
        .children( ".congress-campaign-page-container" );
      const $links = $( this )
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
    });

  });

}( jQuery ) );
