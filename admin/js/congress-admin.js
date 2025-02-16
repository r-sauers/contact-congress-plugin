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

  class AbstractOfficial {
    $container;
    $template;
    $el;
    created;
    constructor() {
      if ( this.constructor == AbstractOfficial ) {
        throw new Error( "Abstract classes can't be instantiated." );
      }
    }
    drawTemplate() {
      const newOfficial = this.$template[0].content.cloneNode( true );
      this.$container.append( newOfficial );
      const $official = this.$container.children().last();
      this.$el = $official;
      this.addEditingEvents();
    }
    toggleEdit() {
      this.$el.toggleClass( "congress-editable" );
    }
    removeTemplate() {
      this.$el.remove();
    }
    create( formData ) {
      this.createRequest( formData )
        .then( () => {
          this.toggleEdit();
        })
        .catch( ( err ) => {
          throw err;
        });
    }
    delete() {
      this.deleteRequest()
        .then( () => {
          this.$el.remove();
        })
        .catch( ( err ) => {
          throw err;
        });
    }
    edit( formData ) {
      this.editRequest( formData )
        .then( () => {
          this.toggleEdit();
        })
        .catch( ( err ) => {
          throw err;
        });
    }
    restore() {
      this.restoreRequest()
        .then( ( formData ) => {
          const form = this.$el.find( "form" )[0];
          for ( const key in formData ) {
            form[key].value = formData[key];
          }
          this.toggleEdit();
        })
        .catch( ( err ) => {
          throw err;
        });
    }
    _eventListenerHelper( evt ) {
      evt.preventDefault();
      evt.data.this[evt.data.funcName]( evt );
    }
    addEditingEvents() {
      const $readonlyContainer = this.$el.children( ".congress-official-readonly" ).first();
      const $editableContainer = this.$el.children( ".congress-official-editable" ).first();

      // enable editing toggling
      $readonlyContainer
        .find( ".congress-edit-button" )
        .first()
        .on( "click", null, {funcName: "toggleEdit", this: this}, this._eventListenerHelper );

      // handle form
      let $form;
      if ( "FORM" === $editableContainer.prop( "tagName" ) ) {
        $form = $editableContainer;
      } else {
        $form = $editableContainer
          .find( "form" )
          .first();
      }
      $form.on( "submit", null, {funcName: "handleForm", this: this}, this._eventListenerHelper );

      // enable representative deletion
      $readonlyContainer
        .find( ".congress-delete-button" )
        .first()
        .on( "click", null, {funcName: "delete", this: this}, this._eventListenerHelper );
    }
    handleForm( evt ) {

      evt.preventDefault();

      if ( "cancel" === evt.originalEvent.submitter.value ) {
        if ( this.created ) {
          this.restore();
        } else {
          this.removeTemplate();
        }
      } else {
        this.create();
      }
    }
    createRequest( formData ) {}
    deleteRequest() {}
    editRequest( formData ) {}
    restoreRequest() {}
  }

  class Staffer extends AbstractOfficial {
    repID;
    constructor({ repID, $repContainer }) {
      super();
      this.repID = repID;
      this.$container = $repContainer.find( ".congress-staffers-list" );
      this.$template = $( "#congress-staffer-template" );
      this.created = false;
    }
    static fromHTML( $el, args ) {
      const staffer = new Staffer( args );
      staffer.$el = $el;
      staffer.stafferID = $el[0].id.replace( "congress-staffer-", "" );
      staffer.created = true;
      return staffer;
    }
    createRequest( formData ) {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    deleteRequest() {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    editRequest( formData ) {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    restoreRequest() {
      return new Promise( ( resolve ) => {
        resolve({});
      });
    }
  }

  class Rep extends AbstractOfficial {
    repID;
    constructor() {
      super();
      this.$container = $( "#congress-reps-container" );
      this.$template = $( "#congress-rep-template" );
      this.created = false;
    }
    static fromHTML( $el ) {
      const rep = new Rep();
      rep.$el = $el;
      rep.repID = $el[0].id.replace( "congress-rep-", "" );
      rep.created = true;
      return rep;
    }
    createRequest( formData ) {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    deleteRequest() {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    editRequest( formData ) {
      return new Promise( ( resolve ) => {
        resolve();
      });
    }
    restoreRequest() {
      return new Promise( ( resolve ) => {
        resolve({});
      });
    }
    addEditingEvents() {
      super.addEditingEvents();
      const instance = this;
      this.$el.find( ".congress-staffer-toggle" ).each( function() {
        $( this ).on( "click", null, instance.$el, instance.toggleStaffers );
      });
    }
    toggleStaffers( evt ) {
      evt.preventDefault();
      evt.data.toggleClass( "congress-closed" );
    }
    getID() {
      return this.repID;
    }
  }

  class OfficialFactory {
    constructor( type, args ) {
      this.type = type;
      this.args = args;
    }
    createOfficial() {
      if ( "rep" === this.type ) {
        return new Rep( this.args );
      } else {
        return new Staffer( this.args );
      }
    }
  }

  function addOfficial( evt ) {
    evt.preventDefault();

    const official = evt.data.createOfficial();
    official.drawTemplate();
  }


  $( () => {

    const repFactory = new OfficialFactory( "rep" );
    $( "#congress-add-rep-button" ).on( "click", null, repFactory, addOfficial );

    $( ".congress-rep-container" ).each( function() {

      const $stafferContainer = $( this ).children( ".congress-staffer-container" ).first();
      const $staffersList = $stafferContainer.children( ".congress-staffers-list" ).first();

      const rep = Rep.fromHTML( $( this ) );
      rep.addEditingEvents();

      const $repContainer = $( this );
      const stafferFactory = new OfficialFactory( "staffer", {
        repID: rep.getID(),
        $repContainer: $repContainer
      });
      $stafferContainer.find( ".congress-add-staffer-button" ).first().on( "click", null, stafferFactory, addOfficial );


      $staffersList.children().each( function() {
        const staffer = Staffer.fromHTML( $( this ), {
          repID: rep.getID(),
          $repContainer: $repContainer
        });
        staffer.addEditingEvents();
      });

    });
  });

}( jQuery ) );
