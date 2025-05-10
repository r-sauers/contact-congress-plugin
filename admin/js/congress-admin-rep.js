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
      this.$container.prepend( newOfficial );
      const $official = this.$container.children().first();
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

    /**
     * Abstract method to init form fields with values specified by fieldData.
     *
     * @param {Object} fieldData
     */
    initField() {}
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
     * Constructs a Staffer from the server's JSON response.
     *
     * @param {id} repID
     * @param {JQueryElement} $repContainer
     * @param {string} createNonce
     * @param {Object} json
     */
    static fromJSON( repID, $repContainer, createNonce, json ) {
      const staffer = new Staffer({ repID, $repContainer, createNonce });
      staffer.drawTemplate();
      staffer.stafferID = parseInt( json.id );
      staffer.initHTML({
        repID: repID,
        id: json.id,
        title: json.title,
        firstName: json.firstName,
        lastName: json.lastName,
        email: json.email,
        deleteNonce: json.deleteNonce,
        editNonce: json.editNonce
      });
      staffer.toggleEdit();
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
     * Initializes the HTML with values and event handlers.
     */
    initHTML({ repID, id, title, firstName, lastName, email, editNonce, deleteNonce }) {
      const editForm = this.getForm();
      this.$el[0].id = this.$el[0].id.replace( "--", `-${repID}-${id}` );

      editForm.rep_id.value = repID;
      editForm.staffer_id.value = id;

      editForm.title.value = title;
      editForm.title.id = editForm.title.id.replace( "---", `-${repID}-${id}-` );
      editForm.title.previousElementSibling.setAttribute( "for", editForm.title.id );

      editForm.first_name.value = firstName;
      editForm.first_name.id = editForm.first_name.id.replace( "---", `-${repID}-${id}-` );
      editForm.first_name.previousElementSibling.setAttribute( "for", editForm.first_name.id );

      editForm.last_name.value = lastName;
      editForm.last_name.id = editForm.last_name.id.replace( "---", `-${repID}-${id}-` );
      editForm.last_name.previousElementSibling.setAttribute( "for", editForm.last_name.id );

      editForm.email.value = email;
      editForm.email.id = editForm.email.id.replace( "---", `-${repID}-${id}-` );
      editForm.email.previousElementSibling.setAttribute( "for", editForm.email.id );

      editForm._wpnonce.value = editNonce;
      this.$el.find( ".congress-official-readonly > span" )
        .text(
`${title} ${firstName} ${lastName} \
(${email})`
        );

      const deleteForm = this.$el.find( ".congress-staffer-delete-form" )[0];
      deleteForm._wpnonce.value = deleteNonce;
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
            I.stafferID = id;
            const editForm = I.getForm();

            I.initHTML({
              repID: I.repID,
              id: id,
              title: editForm.title.value,
              firstName: editForm.first_name.value,
              lastName: editForm.last_name.value,
              email: editForm.email.value,
              editNonce: editNonce,
              deleteNonce: deleteNonce
            });

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

    /**
     * @see AbstractOfficial.initField()
     */
    initField({title, firstName, lastName, email}) {
      const editForm = this.getForm();

      editForm.title.value = title;
      editForm.first_name.value = firstName;
      editForm.last_name.value = lastName;
      editForm.email.value = email;
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
     * Gets the container to store representatives in.
     *
     * @return {JQueryElement}
     */
    static getContainer() {
      return $( "#congress-reps-container" );
    }

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
     * Constructs a representative from the server's JSON response.
     *
     * @param {Object} json
     */
    static fromJSON( json ) {
      const rep = new Rep();
      rep.drawTemplate();
      rep.repID = parseInt( json.id );
      rep.initHTML({
        id: json.id,
        level: json.level,
        state: json.state,
        district: json.district,
        title: json.title,
        firstName: json.firstName,
        lastName: json.lastName,
        editNonce: json.editNonce,
        deleteNonce: json.deleteNonce,
        createNonce: json.createNonce,
        staffers: json.staffers || null
      });
      rep.toggleEdit();
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
     * Initializes the HTML with values and event handlers.
     *
     * @param {number} id
     * @param {'federal'|'state'} level
     * @param {string} state
     * @param {string|null} district
     * @param {'Representative'|'Senator'} title
     * @param {string} firstName
     * @param {string} lastName
     * @param {string} editNonce
     * @param {string} deleteNonce
     * @param {string} createNonce
     * @param {array<Object>|null} staffers
     */
    initHTML({ id, level, state, district = null, title, firstName, lastName, editNonce, deleteNonce, createNonce, staffers = null }) {

      const editForm = this.getForm();

      editForm.title.value = title;
      editForm.title.id = editForm.title.id.replace( "--", `-${id}-` );
      editForm.title.previousElementSibling.setAttribute( "for", editForm.title.id );

      editForm.level.value = level;
      editForm.level.id = editForm.level.id.replace( "--", `-${id}-` );
      editForm.level.previousElementSibling.setAttribute( "for", editForm.level.id );

      editForm.state.value = state;
      editForm.state.id = editForm.state.id.replace( "--", `-${id}-` );
      editForm.state.previousElementSibling.setAttribute( "for", editForm.state.id );

      editForm.district.value = district || "";
      editForm.district.id = editForm.district.id.replace( "--", `-${id}-` );
      editForm.district.previousElementSibling.setAttribute( "for", editForm.district.id );

      editForm.first_name.value = firstName;
      editForm.first_name.id = editForm.first_name.id.replace( "--", `-${id}-` );
      editForm.first_name.previousElementSibling.setAttribute( "for", editForm.first_name.id );

      editForm.last_name.value = lastName;
      editForm.last_name.id = editForm.last_name.id.replace( "--", `-${id}-` );
      editForm.last_name.previousElementSibling.setAttribute( "for", editForm.last_name.id );

      editForm.rep_id.value = id;
      editForm._wpnonce.value = editNonce;

      level = level.toProperCase();

      this.$el[0].id = `congress-rep-${id}`;
      const districtText = ( "" === district ? "" : ` District ${district}` );
      this.$el.find( ".congress-official-readonly > span" )
        .text( `${level} ${title} ${firstName} ${lastName} (${state}${districtText})` );

      const $btn = this.$el.find( "#congress-rep--add-staffer" );
      $btn[0].id = `#congress-rep-${id}-add-staffer`;
      const stafferFactory = new OfficialFactory(
        "staffer",
        {
          repID: id,
          $repContainer: this.$el,
          createNonce: createNonce
        },
        {
          title: title,
          firstName: firstName,
          lastName: lastName,
          email: ""
        }
      );
      $btn.on( "click", null, stafferFactory, addOfficial );

      if ( null !== staffers ) {
        for ( let staffer of staffers ) {
          Staffer.fromJSON( id, this.$el, createNonce, staffer );
        }
      }

      const deleteForm = this.$el.find( ".congress-rep-delete-form" )[0];
      deleteForm._wpnonce.value = deleteNonce;

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

            I.initHTML({
              id: id,
              level: editForm.level.value,
              state: editForm.state.value,
              district: editForm.district.value,
              title: editForm.title.value,
              firstName: editForm.first_name.value,
              lastName: editForm.last_name.value,
              editNonce: editNonce,
              deleteNonce: deleteNonce,
              createNonce: createNonce
            });

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
            const districtText = ( "" === form.district.value ? "" : ` District ${form.district.value}` );
            I.$el.children( ".congress-official-readonly" )
              .children( "span" )
              .text(
`${form.level.value.toProperCase()} ${form.title.value} ${form.first_name.value} ${form.last_name.value} \
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

    /**
     * @see AbstractOfficial.initField()
     */
    initField({title, level, state, district, firstName, lastName}) {
      const editForm = this.getForm();

      editForm.title.value = title;
      editForm.level.value = level;
      editForm.state.value = state;
      editForm.district.value = district;
      editForm.first_name.value = firstName;
      editForm.last_name.value = lastName;
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
     * @param {Object} defaultFields are attributes to @see AbstractOfficial.initField() .
     * @param {boolean} firstTimeOnly when set to true, inits fields
     * with defaultFields only when the container is emtpy.
     */
    constructor( type, args, defaultFields = null, firstTimeOnly = true ) {
      this.type = type;
      this.args = args;
      this.defaultFields = defaultFields;
      this.firstTimeOnly = firstTimeOnly;
    }

    /**
     * Creates an official.
     *
     * @returns {AbstractOfficial}
     */
    createOfficial() {
      let official = null;
      if ( "rep" === this.type ) {
        official = new Rep( this.args );
      } else {
        official = new Staffer( this.args );
      }

      official.drawTemplate();

      if ( null !== this.defaultFields ) {
        if ( ! this.firstTimeOnly ) {
          official.initField( this.defaultFields );
        } else if ( 1 === official.$container.children().length ) {
          official.initField( this.defaultFields );
        }
      }

      return official;
    }
  }

  /**
   * Creates an AbstractOfficial on the page using an OfficialFactory.
   *
   * @param {ClickEvent} evt
   */
  function addOfficial( evt ) {
    evt.preventDefault();
    evt.data.createOfficial();
  }

  /**
   * Initializes event handlers for the sync button.
   */
  function initSync() {

    $( "#congress-sync-form" ).on( "submit", function( evt ) {

      evt.preventDefault();
      const form = evt.target;

      $( "#congress-sync-reps-hint" ).text( "Loading..." );
      $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-error", false );
      $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-success", false );

      let body = {
          action: form.attributes.action.value,
          _wpnonce: congressSyncRepsNonce
      };

      if ( form.state.value ) {
        body.state = form.state.value;
      }
      if ( form.level.value ) {
        body.level = form.level.value;
      }

      const filterForm = $( "#congress-filter-form" )[0];

      $.post(
        ajaxurl,
        body,
        function( data ) {

          for ( let rep of data.reps_added ) {
            if (
                (
                  "" === filterForm.level.value ||
                  rep.level === filterForm.level.value
                ) &&
                (
                  "" === filterForm.title.value ||
                  rep.title === filterForm.title.value
                ) &&
                (
                  "" === filterForm.state.value ||
                  rep.state === filterForm.state.value
                )
              ) {
                Rep.fromJSON( rep );
            }
          }

          for ( let rep of data.reps_removed ) {
            $( `#congress-rep-${rep.id}` ).remove();
          }

          $( "#congress-sync-reps-hint" ).text( "Successfully Synced!" );
          $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-error", false );
          $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-success", true );
        }
      ).fail( function( data ) {
        $( "#congress-sync-reps-hint" ).text( "Failed to sync!" );
        $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-error", true );
        $( "#congress-sync-reps-hint" ).toggleClass( "congress-form-success", false );
      });
    });
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
      const repEditForm = rep.getForm();

      const $repContainer = $( this );
      const stafferFactory = new OfficialFactory(
        "staffer",
        {
          repID: rep.getID(),
          $repContainer: $repContainer,
          createNonce: $stafferContainer.find( ".congress-add-staffer-button" ).first().attr( "createNonce" )
        },
        {
          title: repEditForm.title.value,
          firstName: repEditForm.first_name.value,
          lastName: repEditForm.last_name.value,
          email: ""
        }
      );
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

  /**
   * Adds event handlers for the filter form.
   */
  function initFilter() {

    const $filterHint = $( "#congress-filter-hint" );
    const filterForm = $( "#congress-filter-form" )[0];
    filterForm.level.oninput = () => $filterHint.text( "" );
    filterForm.title.oninput = () => $filterHint.text( "" );
    filterForm.state.oninput = () => $filterHint.text( "" );

    $( filterForm ).on( "submit", function( evt ) {
      evt.preventDefault();

      Rep.getContainer().empty();
      const body = {
          "action": filterForm.attributes.action.value,
          "_wpnonce": filterForm._wpnonce.value
      };
      if ( filterForm.level.value ) {
        body.level = filterForm.level.value;
      }
      if ( filterForm.title.value ) {
        body.title = filterForm.title.value;
      }
      if ( filterForm.state.value ) {
        body.state = filterForm.state.value;
      }

      $filterHint.text( "Loading..." );
      $filterHint.toggleClass( "congress-form-success", false );
      $filterHint.toggleClass( "congress-form-error", false );

      $.get(
        ajaxurl,
        body,
        function( reps ) {
          for ( const rep of Object.values( reps ) ) {
            Rep.fromJSON( rep );
          }
          $filterHint.text( "Success!" );
          $filterHint.toggleClass( "congress-form-success", true );
          $filterHint.toggleClass( "congress-form-error", false );
        }
      ).fail( function({ error }) {

        $filterHint.text( error );
        $filterHint.toggleClass( "congress-form-success", false );
        $filterHint.toggleClass( "congress-form-error", true );
      });
    });
  }

  $( () => {
    initOfficials();
    initSync();
    initFilter();
  });
}( jQuery ) );
