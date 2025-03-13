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
   * An abstraction of representatives and staffers that helps manage CRUD operations.
   */
  class AbstractOfficial {

    /**
     * The container for this type of official (where new officials should be appended).
     *
     * @var jQueryElement
     */
    $container;

    /**
     * The template used to build new officials.
     *
     * @var jQueryElement
     */
    $template;

    /**
     * The root element of the official's DOM tree.
     *
     * @var jQueryElement
     */
    $el;

    /**
     * A boolean representing whether or not the official has been created in the DB.
     *
     * @var boolean
     */
    created;

    /**
     * Storage to restore form data when an operation is cancelled.
     *
     * @var FormData
     */
    formSave;

    /**
     * AbstractOfficials cannot be instantiated!
     */
    constructor() {
      if ( this.constructor == AbstractOfficial ) {
        throw new Error( "Abstract classes can't be instantiated." );
      }
    }

    /**
     * Used to create the DOM tree of the official based on $template and add it to $container.
     */
    drawTemplate() {
      const newOfficial = this.$template[0].content.cloneNode( true );
      this.$container.append( newOfficial );
      const $official = this.$container.children().last();
      this.$el = $official;
      this.addEditingEvents();
    }

    /**
     * Toggles the editing user interface.
     *
     * @param {boolean} saveChanges ensures the form is not reset to the previous values.
     */
    toggleEdit( saveChanges = true ) {
      this.$el.toggleClass( "congress-editable" );

      if ( this.$el.hasClass( "congress-editable" ) ) {
        delete this.formSave;
        this.formSave = new FormData( this.getForm() );

      } else if ( ! saveChanges && this.formSave ) {
        const form = this.getForm();
        for ( const key of this.formSave.keys() ) {
          form[key].value = this.formSave.get( key );
        }
      }
    }

    /**
     * Removes the DOM tree.
     */
    removeTemplate() {
      this.$el.remove();
    }

    /**
     * Initiates the process of creating the official and updates DOM with result.
     *
     * @param {SubmitEvent}
     */
    create( evt ) {
      const formData = new FormData( evt.target );
      this.createRequest( formData )
        .then( () => {
          this.created = true;
          this.toggleEdit();
        })
        .catch( ( err ) => {
          throw err;
        });
    }

    /**
     * Initiates the process of deleting the official and updates DOM with result.
     *
     * @param {SubmitEvent}
     */
    delete( evt ) {
      const formData = new FormData( evt.target );
      this.deleteRequest( formData )
        .then( () => {
          this.$el.remove();
        })
        .catch( ( err ) => {
          throw err;
        });
    }

    /**
     * Initiates the process of editing the official and updates DOM with result.
     *
     * @param {SubmitEvent}
     */
    edit( evt ) {
      const formData = new FormData( evt.target );
      this.editRequest( formData )
        .then( () => {
          this.toggleEdit();
        })
        .catch( ( err ) => {
          throw err;
        });
    }

    /**
     * A helper function to handle DOM events using class methods.
     *
     * Without this, there is no easy way to access the class method during a DOM event.
     *
     * @param {Event}
     */
    _eventListenerHelper( evt ) {
      evt.preventDefault();
      evt.data.this[evt.data.funcName]( evt );
    }

    /**
     * Adds event handlers to the DOM.
     */
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
        .find( ".congress-official-delete-form" )
        .first()
        .on( "submit", null, {funcName: "delete", this: this}, this._eventListenerHelper );
    }

    /**
     * Handles form events for the editing form.
     *
     * @param {SubmitEvent}
     */
    handleForm( evt ) {

      evt.preventDefault();

      if ( "cancel" === evt.originalEvent.submitter.value ) {
        if ( this.created ) {
          this.toggleEdit( false );
        } else {
          delete this.formSave;
          this.removeTemplate();
          delete this;
        }
      } else {
        if ( this.created ) {
          this.edit( evt );
        } else {
          this.create( evt );
        }
      }
    }

    /**
     * Abstract method to get the editor form.
     *
     * @returns {HTMLFormElement}
     */
    getForm() {}

    /**
     * Abstract method to send a create request.
     *
     * @param {FormData} formData
     * @returns {Promise}
     */
    createRequest() {}

    /**
     * Abstract method to send a delete request.
     *
     * @param {FormData} formData
     * @returns {Promise}
     */
    deleteRequest() {}

    /**
     * Abstract method to send a edit request.
     *
     * @param {FormData} formData
     * @returns {Promise}
     */
    editRequest() {}
  }

  /**
   * Helps manage CRUD operations for Staffer
   */
  class Staffer extends AbstractOfficial {

    /**
     * The id of the staffer's representative.
     */
    repID;

    /**
     * The id of the staffer.
     */
    stafferID;

    /*
     * createNonce is the nonce used to request the staffer be created.
     */
    createNonce;

    /**
     * Constructs a Staffer that has no information and doesn't exist in the DB.
     *
     * @param {number} repID is the id of the staffer's representative.
     * @param {jQueryElement} $repContainer is the container for the representative's staffers.
     * @param {string} createNonce is the nonce used to request the staffer be created.
     */
    constructor({ repID, $repContainer, createNonce }) {
      super();
      this.repID = repID;
      this.$container = $repContainer.find( ".congress-staffers-list" );
      this.$template = $( "#congress-staffer-template" );
      this.created = false;
      this.createNonce = createNonce;
    }

    /**
     * Constructs a Staffer from HTML (drawn with PHP).
     *
     * @param {jQueryElement} $el is the root of the DOM tree of the Staffer.
     * @param {{repID: number, $repContainer: jQueryElement}} args are arguments for the constructor.
     */
    static fromHTML( $el, args ) {
      const staffer = new Staffer( args );
      staffer.$el = $el;
      staffer.stafferID = parseInt( $el.find( "form" )[0].staffer_id.value );
      staffer.created = true;
      return staffer;
    }

    /**
     * @see AbstractOfficial.getForm()
     */
    getForm() {
      return this.$el.find( ".congress-staffer-edit-form" )[0];
    }

    /**
     * @see AbstractOfficial.createRequest()
     */
    createRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "add_staffer",
            "rep_id": I.repID,
            title: formData.get( "title" ),
            "first_name": formData.get( "first_name" ),
            "last_name": formData.get( "last_name" ),
            email: formData.get( "email" ),
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function({ rawID, editNonce, deleteNonce }) {

            let id = parseInt( rawID );

            const editForm = I.getForm();
            I.$el[0].id = I.$el[0].id.replace( "--", `-${I.repID}-${id}` );
            editForm.staffer_id.value = id;
            editForm._wpnonce.value = editNonce;
            I.$el.find( ".congress-official-readonly > span" )
              .text(
`${editForm.title.value} ${editForm.first_name.value} ${editForm.last_name.value} \
(${editForm.email.value})`
              );
            I.stafferID = id;

            const deleteForm = I.$el.find( ".congress-staffer-delete-form" )[0];
            deleteForm._wpnonce.value = deleteNonce;

            resolve( id );
          }
        );
      });
    }

    /**
     * @see AbstractOfficial.deleteRequest()
     */
    deleteRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "delete_staffer",
            "rep_id": I.repID,
            "staffer_id": I.stafferID,
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function() {
            resolve();
          }
        );
      });
    }

    /**
     * @see AbstractOfficial.editRequest()
     */
    editRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "update_staffer",
            "rep_id": I.repID,
            "staffer_id": I.stafferID,
            title: formData.get( "title" ),
            "first_name": formData.get( "first_name" ),
            "last_name": formData.get( "last_name" ),
            email: formData.get( "email" ),
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function( rawID ) {

            let id = parseInt( rawID );

            const form = I.getForm();
            I.$el.find( ".congress-official-readonly > span" )
              .text(
`${form.title.value} ${form.first_name.value} ${form.last_name.value} \
(${form.email.value})`
              );

            resolve( id );
          }
        );
      });
    }

    /**
     * @see AbstractOfficial.drawTemplate()
     */
    drawTemplate() {
      super.drawTemplate();
      this.getForm()._wpnonce.value = this.createNonce;
    }
  }

  /**
   * Helps manage CRUD operations for representatives.
   */
  class Rep extends AbstractOfficial {

    /**
     * The id of the representative.
     */
    repID;

    /**
     * Constructs a Staffer that has no information and doesn't exist in the DB.
     */
    constructor() {
      super();
      this.$container = $( "#congress-reps-container" );
      this.$template = $( "#congress-rep-template" );
      this.created = false;
    }

    /**
     * Constructs a representative from HTML (drawn with PHP).
     *
     * @param {jQueryElement} $el is the root of the DOM tree of the Rep.
     */
    static fromHTML( $el ) {
      const rep = new Rep();
      rep.$el = $el;
      rep.repID = parseInt( $el.find( "form" )[0].rep_id.value );
      rep.created = true;
      return rep;
    }

    /**
     * @see AbstractOfficial.getForm()
     */
    getForm() {
      return this.$el.find( ".congress-rep-edit-form" )[0];
    }

    /**
     * @see AbstractOfficial.createRequest()
     */
    createRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "add_representative",
            title: formData.get( "title" ),
            "first_name": formData.get( "first_name" ),
            "last_name": formData.get( "last_name" ),
            state: formData.get( "state" ),
            district: formData.get( "district" ),
            level: formData.get( "level" ),
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function({ rawID, editNonce, deleteNonce, createNonce }) {

            let id = parseInt( rawID );
            I.repID = id;

            const editForm = I.getForm();
            editForm.rep_id.value = id;
            editForm._wpnonce.value = editNonce;

            I.$el[0].id = I.$el[0].id + id;
            const districtText = ( "" === editForm.district.value ? "" : ` District${editForm.district.value}` );
            I.$el.find( ".congress-official-readonly > span" )
              .text(
`${editForm.level.value} ${editForm.title.value} ${editForm.first_name.value} ${editForm.last_name.value} \
(${editForm.state.value}${districtText})`
              );

            const $btn = I.$el.find( "#congress-rep--add-staffer" );
            $btn[0].id = `#congress-rep-${id}-add-staffer`;
            const stafferFactory = new OfficialFactory( "staffer", {
              repID: id,
              $repContainer: I.$el,
              createNonce: createNonce
            });
            $btn.on( "click", null, stafferFactory, addOfficial );

            const deleteForm = I.$el.find( ".congress-rep-delete-form" )[0];
            deleteForm._wpnonce.value = deleteNonce;

            resolve( id );

          }
        );
      });
    }

    /**
     * @see AbstractOfficial.deleteRequest()
     */
    deleteRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "delete_representative",
            "rep_id": I.repID,
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function() {
            resolve();
          }
        );
      });
    }

    /**
     * @see AbstractOfficial.editRequest()
     */
    editRequest( formData ) {
      const I = this;
      return new Promise( ( resolve ) => {
        $.post(
          ajaxurl,
          {
            action: "update_representative",
            "rep_id": I.repID,
            title: formData.get( "title" ),
            "first_name": formData.get( "first_name" ),
            "last_name": formData.get( "last_name" ),
            state: formData.get( "state" ),
            district: formData.get( "district" ),
            level: formData.get( "level" ),
            "_wpnonce": formData.get( "_wpnonce" )
          },
          function() {

            const form = I.getForm();
            const districtText = ( "" === form.district.value ? "" : ` District${form.district.value}` );
            I.$el.find( ".congress-official-readonly > span" )
              .text(
`${form.level.value} ${form.title.value} ${form.first_name.value} ${form.last_name.value} \
(${form.state.value}${districtText})`
              );

            resolve();
          }
        );
      });
    }

    /**
     * @see AbstractOfficial.addEditingEvents()
     */
    addEditingEvents() {
      super.addEditingEvents();
      const instance = this;
      this.$el.find( ".congress-staffer-toggle" ).each( function() {
        $( this ).on( "click", null, instance.$el, instance.toggleStaffers );
      });
    }

    /**
     * Toggles the staffer container display.
     *
     * @param {ClickEvent} evt
     */
    toggleStaffers( evt ) {
      evt.preventDefault();
      evt.data.toggleClass( "congress-closed" );
    }

    /**
     * @returns {number} the representative's id.
     */
    getID() {
      return this.repID;
    }
  }

  /**
   * A factory to create officials.
   */
  class OfficialFactory {

    /**
     * Constructs the factory.
     *
     * @param {"rep"|"staffer"} type
     * @param {Object} arguments for the AbstractOfficial's constructor.
     */
    constructor( type, args ) {
      this.type = type;
      this.args = args;
    }

    /**
     * Creates an official.
     *
     * @returns {AbstractOfficial}
     */
    createOfficial() {
      if ( "rep" === this.type ) {
        return new Rep( this.args );
      } else {
        return new Staffer( this.args );
      }
    }
  }

  /**
   * Creates an AbstractOfficial on the page using an OfficialFactory.
   *
   * @param {ClickEvent} evt
   */
  function addOfficial( evt ) {
    evt.preventDefault();

    const official = evt.data.createOfficial();
    official.drawTemplate();
  }


  /**
   * Initializes objects and event handlers from HTML generated by PHP.
   */
  function initOfficials() {
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
        $repContainer: $repContainer,
        createNonce: $stafferContainer.find( ".congress-add-staffer-button" ).first().attr( "createNonce" )
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
  }

  $( () => {
    initOfficials();
  });
}( jQuery ) );
