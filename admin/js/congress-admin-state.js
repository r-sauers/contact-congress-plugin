( function( $ ) {
  "use strict";

  /**
   * JS code for the admin states page.
   */

  /**
   * A class to manage the state table.
   */
  class StateTable {

    /**
     * The rows of the state table.
     *
     * @type {Array<StateRow>}
     */
    _allRows;

    /**
     * The rows of the state table that aren't hidden.
     *
     * @type {Array<StateRow>}
     */
    _displayedRows;

    /**
     * The headers of the table that can be toggled for sorting.
     *
     * @type {Array<SortableHeader>}
     */
    _sortableHeaders;

    /**
     * The header of the table that allows select all, deselect all functionality.
     *
     * @type {StateCheckboxHeader}
     */
    _checkboxHeader;

    /**
     * The parent element of the table.
     *
     * @type {HTMLTableElement}
     */
    element;

    /**
     * The bulk action dropdown.
     *
     * @type {ActionDropdown}
     */
    _actionDropdown;

    /**
     * Constructs itself from the HTML on the page.
     */
    constructor() {

      this.element = document.getElementById( "congress-bulk-action-table" );

      this._allRows = [];
      this._displayedRows = [];

      const $rows = $( this.element ).find( ".congress-state-row" );
      for ( const rowEl of $rows ) {
        const row = new StateRow( rowEl, this );
        this._allRows.push( row );
        this._displayedRows.push( row );
      }

      this._sortableHeaders = [];

      const headerRowEl = $( this.element )
        .find( "thead" )
        .first()
        .find( "tr" )[0];
      this._sortableHeaders.push( new StateNameHeader( headerRowEl, this ) );
      this._sortableHeaders.push( new StateStatusHeader( headerRowEl, this ) );
      this._sortableHeaders.push( new StateAPIHeader( headerRowEl, this ) );
      this._sortableHeaders.push( new StateStateSyncHeader( headerRowEl, this ) );
      this._sortableHeaders.push( new StateFederalSyncHeader( headerRowEl, this ) );
      this._checkboxHeader = new StateCheckboxHeader( headerRowEl, this );

      this._actionDropdown = new ActionDropdown( this );
    }

    /**
     * Clears the Aria attributes for a sorted table.
     */
    clearSortAria() {
      for ( const sortableHeader of this._sortableHeaders ) {
        sortableHeader.clearSortAria();
      }
    }

    /**
     * Gets all of the state rows.
     *
     * @returns {Array<StateRow>}
     */
    getRows() {
      return this._allRows;
    }

    /**
     * Gets the state rows that are selected.
     *
     * @returns {Array<StateRow>}
     */
    getSelectedRows() {
      const selectedRows = [];

      for ( const row of this._displayedRows ) {
        if ( row.isSelected() ) {
          selectedRows.push( row );
        }
      }

      return selectedRows;
    }

    /**
     * Filters the state rows being displayed using a callback function.
     *
     * @param {(stateRow: StateRow) => boolean} callback
     */
    filter( callback ) {

      this._displayedRows = [];

      for ( const row of this._allRows ) {
        if ( callback( row ) ) {
          this._displayedRows.push( row );
          row.show();
        } else {
          row.deselect();
          row.hide();
        }
      }

      this.refreshSelection();
    }

    /**
     * Sorts the state rows being displayed using a callback function.
     *
     * The callback return value should be:
     * - Equal to 0 if a === b.
     * - Less than 0 if a < b.
     * - Greater than 0 if a > b.
     *
     * @param {(a: StateRow, b: StateRow) => number} callback
     */
    sort( callback ) {
      this._allRows.sort( callback );

      const tbody = $( this.element ).find( "tbody" )[0];

      for ( const row of this._allRows ) {
        tbody.append( ...row.getElements() );
      }
    }

    /**
     * Selects all state rows.
     */
    selectAll() {
      for ( const row of this._displayedRows ) {
        row.select();
      }
      this._checkboxHeader.toggleCheck( true );
      this._actionDropdown.clearFormHint();
    }

    /**
     * Deselects all state rows.
     */
    deselectAll() {
      for ( const row of this._displayedRows ) {
        row.deselect();
      }
      this._checkboxHeader.toggleCheck( false );
      this._actionDropdown.clearFormHint();
    }

    /**
     * Checks state row selections to determine the value of the checkbox header.
     */
    refreshSelection() {

      for ( const row of this._allRows ) {
        if ( ! row.isSelected() ) {
          this._checkboxHeader.toggleCheck( false );
          return;
        }
      }

      this._checkboxHeader.toggleCheck( true );
      this._actionDropdown.clearFormHint();
    }
  }

  /**
   * A class to manage the state rows.
   */
  class StateRow {

    /*
     * The parent element of the row.
     *
     * @type {HTMLTableRowElement}
     */
    _rowEl;

    /*
     * The parent element of the state row details expansion.
     *
     * @type {HTMLTableRowElement}
     */
    _expansionEl;

    /**
     * The button used to toggle the detail expansion.
     *
     * @type {HTMLButtonElement}
     */
    _expandToggle;

    /**
     * The header of the button used for expansion toggle.
     *
     * @type {HTMLTableCellElement}
     */
    _expansionHeader;

    /**
     * The cell containing the selection checkbox.
     *
     * @type {HTMLTableCellElement}
     */
    _checkboxCell;

    /**
     * The cell containing the state name.
     *
     * @type {HTMLTableCellElement}
     */
    _stateNameCell;

    /**
     * The cell containing the activation status.
     *
     * @see isActive for more details.
     *
     * @type {HTMLTableCellElement}
     */
    _statusCell;

    /**
     * The cell containing the api status.
     *
     * @see getAPISupport for more details.
     *
     * @type {HTMLTableCellElement}
     */
    _apiCell;

    /**
     * The cell containing the federal syncing status.
     *
     * @type {HTMLTableCellElement}
     */
    _federalSyncCell;

    /**
     * The cell containing the state syncing status.
     *
     * @type {HTMLTableCellElement}
     */
    _stateSyncCell;

    /**
     * The form element for updating the email for syncing.
     *
     * @type {HTMLFormElement}
     */
    _syncEmailForm;

    /**
     * The row's table.
     *
     * @type {StateTable}
     */
    _stateTable;

    /**
     * The state code e.g. 'mn'.
     *
     * @type {string}
     */
    _stateCode;

    /**
     * Constructs the state row from the HTML <tr> tag.
     *
     * @param {HTMLTableRowElement} tableRowEl
     * @param {StateTable} stateTable
     */
    constructor( tableRowEl, stateTable ) {
      this._stateTable = stateTable;
      this._rowEl = tableRowEl;
      this._expansionEl = tableRowEl.nextElementSibling;

      if (
        null === this._expansionEl ||
        ! this._expansionEl.classList.contains( "congress-state-row-expansion" )
      ) {
        throw Error( "DOM Assertion Failed!" );
      }

      this._checkboxCell    = $( this._rowEl ).find( ".congress-state-row-checkbox" )[0];
      this._stateNameCell   = $( this._rowEl ).find( ".congress-state-row-name" )[0];
      this._statusCell      = $( this._rowEl ).find( ".congress-state-row-status" )[0];
      this._apiCell         = $( this._rowEl ).find( ".congress-state-row-api" )[0];
      this._federalSyncCell = $( this._rowEl ).find( ".congress-state-row-federal-sync" )[0];
      this._stateSyncCell   = $( this._rowEl ).find( ".congress-state-row-state-sync" )[0];
      this._expandToggle    = $( this._rowEl ).find( ".congress-state-row-expand" )[0];
      this._expansionHeader = $( this._rowEl ).children().last()[0];
      this._syncEmailForm   = $( this._expansionEl ).find( ".congress-state-row-sync-form" )[0];

      this.toggleDetails( false );

      this._syncEmailForm.onsubmit = ( evt ) => this.setSyncEmailHandler( evt );
      this._syncEmailForm.email.oninput = () => this.clearSyncEmailHint();
      this._expandToggle.onclick = () => this.toggleDetails();

      /**
       * @type {HTMLInputElement}
       */
      const checkboxEl = $( this._checkboxCell ).find( "input" )[0];
      checkboxEl.oninput = () => this._stateTable.refreshSelection();

      this._stateCode = this._syncEmailForm.stateCode.value;
    }

    /**
     * Returns the parent elements making up the state row
     * in the order as they appear in the DOM.
     *
     * @return {Array<HTMLElement}
     */
    getElements() {
      return [ this._rowEl, this._expansionEl ];
    }

    /**
     * Change visibility of the row to hidden.
     */
    hide() {
      $( this._rowEl ).css( "display", "none" );
      $( this._expansionEl ).css( "display", "none" );
    }

    /**
     * Change visibility of the row to visible.
     */
    show() {
      $( this._rowEl ).css( "display", "table-row" );
      $( this._expansionEl ).css( "display", "" );
    }

    /**
     * Is the state row selected?
     *
     * @returns {boolean}
     */
    isSelected() {
      return $( this._checkboxCell ).find( "input" )[0].checked;
    }

    /**
     * Gets the state name.
     *
     * @returns {string}
     */
    getStateName() {
      return $( this._stateNameCell ).text();
    }

    /**
     * Gets the state code e.g. 'mn'.
     *
     * @return {string}
     */
    getStateCode() {
      return this._stateCode;
    }

    /**
     * Gets the activation status of the state.
     *
     * @returns {boolean} true if the state is being used across the plugin.
     */
    isActive() {
      return $( this._statusCell ).hasClass( "congress-activated" );
    }

    /**
     * Gets the API support of the state.
     *
     * @returns {'noSupport'|'disabled'|'enabled'}
     */
    getAPISupport() {
      const $apiCell = $( this._apiCell );
      if ( $apiCell.hasClass( "congress-no-support" ) ) {
        return "noSupport";
      } else if ( $apiCell.hasClass( "congress-enabled" ) ) {
        return "enabled";
      } else if ( $apiCell.hasClass( "congress-disabled" ) ) {
        return "disabled";
      } else {
        throw "DOM Assertion Failed!";
      }
    }

    /**
     * Is the state being used for federal-level syncing?
     *
     * @returns {boolean}
     */
    isSyncingFederal() {
      return $( this._federalSyncCell ).hasClass( "congress-enabled" );
    }

    /**
     * Is the state beign used for state-level syncing?
     *
     * @returns {boolean}
     */
    isSyncingState() {
      return $( this._stateSyncCell ).hasClass( "congress-enabled" );
    }

    /**
     * Toggles the details that expand below.
     *
     * @param {boolean|null} open specifies whether the dropdown should be toggled open/closed.
     */
    toggleDetails( open = null ) {

      const $expansion = $( this._expansionEl );

      if ( null === open ) {
        open = ! $expansion.hasClass( "congress-expanded" );
      }

      $expansion.toggleClass( "congress-expanded", open );
      $( this._expansionHeader ).toggleClass( "congress-expanded", open );

      const $toggle = $( this._expandToggle );
      $toggle.empty();
      if ( open ) {
        $toggle.append( document.createTextNode( "Less" ) );
        $( "<span>" )
          .addClass( "congress-icon-minus" )
          .appendTo( $toggle );
      } else {
        $toggle.append( document.createTextNode( "More" ) );
        $( "<span>" )
          .addClass( "congress-icon-plus" )
          .appendTo( $toggle );
      }
    }

    /**
     * Selects the state row.
     *
     * @param {StateTable|null} stateTable is the row's table if the table's checkbox header should be updated.
     */
    select( stateTable = null ) {
      $( this._checkboxCell )
        .find( "input" )[0]
        .checked = true;

      if ( null !== stateTable ) {
        stateTable.refreshSelection();
      }
    }

    /**
     * Deselects the state row.
     *
     * @param {StateTable} stateTable is the row's table.
     */
    deselect( stateTable = null ) {
      $( this._checkboxCell )
        .find( "input" )[0]
        .checked = false;

      if ( null !== stateTable ) {
        stateTable.refreshSelection();
      }
    }

    /**
     * Toggles the activation status of the row.
     *
     * The activation status displays whether or not the state is being used across the plugin.
     *
     * @param {boolean|null} active
     */
    toggleStatus( active = null ) {
      const $status = $( this._statusCell );
      if ( null === active ) {
        active = $status.hasClass( "congress-activated" );
      }

      if ( active ) {
        $status.toggleClass( "congress-deactivated", false );
        $status.toggleClass( "congress-activated", true );
        $status.text( "Active!" );
      } else {
        $status.toggleClass( "congress-deactivated", true );
        $status.toggleClass( "congress-activated", false );
        $status.text( "Deactivated" );
      }
    }

    /**
     * Enables the state API.
     *
     * @returns {boolean} true on success, false on failure.
     */
    enableAPI() {
      const $api = this._apiCell;
      if ( $api.hasClass( "congress-no-support" ) ) {
        return false;
      }

      $api.toggleClass( "congress-enabled", true );
      $api.toggleClass( "congress-disabled", false );
      return true;
    }

    /**
     * Disables the state API.
     *
     * @returns {boolean} true on success, false on failure.
     */
    disableAPI() {
      const $api = this._apiCell;
      if ( $api.hasClass( "congress-no-support" ) ) {
        return false;
      }

      $api.toggleClass( "congress-enabled", false );
      $api.toggleClass( "congress-disabled", true );
      return true;
    }

    /**
     * Toggles the activation status of the row.
     *
     * The activation status displays whether or not the state is being used across the plugin.
     *
     * @param {boolean|null} enabled
     */
    toggleFederalSync( enabled = null ) {
      const $federalSync = $( this._federalSyncCell );
      if ( null === enabled ) {
        enabled = $federalSync.hasClass( "congress-enabled" );
      }

      if ( enabled ) {
        $federalSync.toggleClass( "congress-disabled", false );
        $federalSync.toggleClass( "congress-enabled", true );
        $federalSync.text( "Enabled!" );
      } else {
        $federalSync.toggleClass( "congress-disabled", true );
        $federalSync.toggleClass( "congress-enabled", false );
        $federalSync.text( "Disabled" );
      }
    }

    /**
     * Toggles the activation status of the row.
     *
     * The activation status displays whether or not the state is being used across the plugin.
     *
     * @param {boolean|null} enabled
     */
    toggleStateSync( enabled = null ) {
      const $stateSync = $( this._stateSyncCell );
      if ( null === enabled ) {
        enabled = $stateSync.hasClass( "congress-enabled" );
      }

      if ( enabled ) {
        $stateSync.toggleClass( "congress-disabled", false );
        $stateSync.toggleClass( "congress-enabled", true );
        $stateSync.text( "Enabled!" );
      } else {
        $stateSync.toggleClass( "congress-disabled", true );
        $stateSync.toggleClass( "congress-enabled", false );
        $stateSync.text( "Disabled" );
      }
    }

    /**
     * Clears the hint text in the sync email form.
     */
    clearSyncEmailHint() {
      const $emailHint = $( this._syncEmailForm ).find( ".congress-sync-email-hint" );
      $emailHint.text( "" );
    }

    /**
     * Handler for setting the email for syncing alerts.
     */
    setSyncEmailHandler( evt ) {

      evt.preventDefault();

      const formData = new FormData( evt.target );

      const $emailHint = $( this._syncEmailForm ).find( ".congress-sync-email-hint" );
      $emailHint.toggleClass( "congress-form-success", false );
      $emailHint.toggleClass( "congress-form-error", false );
      $emailHint.text( "Loading..." );

      $.post(
        ajaxurl,
        {
          action: evt.target.getAttribute( "action" ),
          email: formData.get( "email" ),
          state: formData.get( "stateCode" ),
          "_wpnonce": formData.get( "_wpnonce" )
        },
        ( email ) => {
          this._syncEmailForm.email.value = email;
          $emailHint.text( "Success!" );
          $emailHint.toggleClass( "congress-form-success", true );
          $emailHint.toggleClass( "congress-form-error", false );
        })
        .fail( function({ responseJSON }) {
          $emailHint.text( responseJSON.error );
          $emailHint.toggleClass( "congress-form-success", false );
          $emailHint.toggleClass( "congress-form-error", true );
        });
    }
  }

  /**
   * A class to manage the table headers that allow sorting.
   */
  class SortableHeader {

    /**
     * The table the header is part of.
     *
     * @type {StateTable}
     */
    _stateTable;

    /**
     * Whether or not the header is ascending or descending.
     *
     * @type {boolean}
     */
    _ascending;

    /**
     * The button element used to toggle ascending/descending.
     *
     * @type {HTMLButtonElement}
     */
    _buttonEl;

    /**
     * The header element.
     *
     * @type {HTMLTableCellElement}
     */
    _headerEl;

    /**
     * Does not construct, since it is an abstract class.
     *
     * @param {HTMLTableCellElement} headerEl is the table header.
     * @param {StateTable} stateTable
     */
    constructor( headerEl, stateTable ) {

      if ( this.prototype === SortableHeader.prototype ) {
        throw "Can't Instantiate Abstract Class";
      }

      this._ascending = true;
      this._headerEl = headerEl;
      this._stateTable = stateTable;
      this._buttonEl = $( this._headerEl ).find( "button" )[0];
      this._buttonEl.onclick = () => this.handleClick();
    }

    /**
     * The event handler for the click event.
     */
    handleClick() {
      this._ascending = ! this._ascending;
      this._stateTable.clearSortAria();
      this._stateTable.sort(
        ( a, b ) => this.compare( a, b, this._ascending )
      );

      this._buttonEl.ariaPressed = true;
      this._headerEl.ariaSort = this._ascending ? "ascending" : "descending";
    }

    /**
     * Clears the Aria attributes for a sorted table.
     */
    clearSortAria() {
      this._buttonEl.ariaPressed = false;
      this._headerEl.ariaSort = null;
    }

    /**
     * The abstract compare method.
     *
     * @see StateTable.sort
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      throw "Abstract Method";
    }
  }

  /**
   * The state name header.
   */
  class StateNameHeader extends SortableHeader {

    /**
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      const headerEl = $( headerRowEl ).find( ".congress-header-state-name" )[0];
      super( headerEl, stateTable );
    }

    /**
     * @see SortableHeader.compare
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      const aName = a.getStateName();
      const bName = b.getStateName();

      if ( aName === bName ) {
        return 0;
      }

      return ( aName < bName === ascending ) ? -1 : 1;
    }
  }

  /**
   * The state activation status header.
   */
  class StateStatusHeader extends SortableHeader {

    /**
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      const headerEl = $( headerRowEl ).find( ".congress-header-status" )[0];
      super( headerEl, stateTable );
    }

    /**
     * @see SortableHeader.compare
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      return ( +b.isActive() - +a.isActive() ) * ( -2 * ascending + 1 );
    }
  }

  /**
   * The state activation status header.
   */
  class StateAPIHeader extends SortableHeader {

    /**
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      const headerEl = $( headerRowEl ).find( ".congress-header-api" )[0];
      super( headerEl, stateTable );
    }

    /**
     * Compares state rows by the API.
     *
     * The order is: 'noSupport' < 'disabed' < 'enabled'
     *
     * @see SortableHeader.compare
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      const aVal = a.getAPISupport();
      const bVal = b.getAPISupport();

      const reversal = 2 * ascending - 1;

      if ( aVal === bVal ) {
        return 0;
      }

      if ( "noSupport" === aVal ) {
        return -1 * reversal;
      }

      if ( "noSupport" === bVal ) {
        return 1 * reversal;
      }

      if ( "disabled" === aVal ) {
        return -1 * reversal;
      }

      if ( "disabled" === bVal ) {
        return 1 * reversal;
      }
    }
  }

  /**
   * The state state syncing status header.
   */
  class StateStateSyncHeader extends SortableHeader {

    /**
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      const headerEl = $( headerRowEl ).find( ".congress-header-state-sync" )[0];
      super( headerEl, stateTable );
    }

    /**
     * @see SortableHeader.compare
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      return ( +b.isSyncingState() - +a.isSyncingState() ) * ( -2 * ascending + 1 );
    }
  }

  /**
   * The state federal syncing status header.
   */
  class StateFederalSyncHeader extends SortableHeader {

    /**
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      const headerEl = $( headerRowEl ).find( ".congress-header-federal-sync" )[0];
      super( headerEl, stateTable );
    }

    /**
     * @see SortableHeader.sort
     *
     * @param {StateRow} a
     * @param {StateRow} b
     * @apram [boolean} ascending
     */
    compare( a, b, ascending ) {
      return ( +b.isSyncingFederal() - +a.isSyncingFederal() ) * ( -2 * ascending + 1 );
    }
  }

  /**
   * A class to manage the checkbox header.
   */
  class StateCheckboxHeader {

    /**
     * The table the header is part of.
     *
     * @type {StateTable}
     */
    _stateTable;

    /**
     * Whether or not the checkbox (and therefore all row checkboxes) are checked.
     *
     * @type {boolean}
     */
    _checked;

    /**
     * The checkbox element.
     *
     * @type {HTMLButtonElement}
     */
    _checkboxEl;

    /**
     * The header element.
     *
     * @type {HTMLTableCellElement}
     */
    _headerEl;

    /**
     * Does not construct, since it is an abstract class.
     *
     * @param {HTMLTableRowElement} headerRowEl
     * @param {StateTable} stateTable
     */
    constructor( headerRowEl, stateTable ) {
      this._ascending = true;
      this._headerEl = $( headerRowEl ).find( ".congress-header-checkbox" )[0];
      this._stateTable = stateTable;
      this._checkboxEl = $( this._headerEl ).find( "input" )[0];
      this._checkboxEl.oninput = () => this.handleInput();
    }

    /**
     * The event handler for the click event.
     */
    handleInput() {
      this._checked = ! this._checked;

      if ( this._checked ) {
        this._stateTable.selectAll();
      } else {
        this._stateTable.deselectAll();
      }
    }

    /**
     * Toggles whether or not the header is checked.
     *
     * NOTE: This does not make changes to StateTable
     *
     * @param {boolean|null} checked.
     */
    toggleCheck( checked = null ) {
      if ( null === checked ) {
        this._checked = ! this._checked;
      } else {
        this._checked = checked;
      }

      this._checkboxEl.checked = this._checked;
    }
  }

  /**
   * A class to manage the action dropdown.
   */
  class ActionDropdown {

    /**
     * The table to perform bulk actions on.
     *
     * @type {StateTable}
     */
    _stateTable;

    /**
     * The form for the action dropdown.
     *
     * @type {HTMLFormElement}
     */
    _form;

    /**
     * The hint for form results.
     *
     * @type {HTMLSpanElement}
     */
    _formHint;

    /**
     * A map of action names to functions.
     *
     * @type {Object<String,Function}
     */
    _actions;

    /**
     * @param {StateTable} stateTable is the table to perform the bulk actions on.
     */
    constructor( stateTable ) {
      this._stateTable = stateTable;
      this._form = $( "#congress-bulk-action-form" )[0];
      this._formHint = $( "#congress-bulk-action-results" );
      this._form.onsubmit = ( evt ) => this.handleSubmit( evt );
      this._form.action.oninput = () => this.clearFormHint();

      this._actions = {
        "activate_states": this.activateState,
        "deactivate_states": this.deactivateState,
        "enable_federal_sync": this.enableFederalSync,
        "disable_federal_sync": this.disableFederalSync,
        "enable_state_sync": this.enableStateSync,
        "disable_state_sync": this.disableStateSync
      };
    }

    /**
     * Clears the form hint.
     */
    clearFormHint() {
      $( this._formHint ).text( "" );
    }

    /**
     * The handler for bulk actions.
     *
     * @param {SubmitEvent} evt
     */
    handleSubmit( evt ) {
      evt.preventDefault();

      const formData = new FormData( evt.target );
      const states = this._stateTable.getSelectedRows();
      const stateString = states
        .map( ( stateRow ) => stateRow.getStateCode() )
        .join( "," );

      const action = formData.get( "action" );

      if ( "none" === action || "" === stateString ) {
        return;
      }

      const $formHint = $( this._formHint );
      $formHint.text( "Loading..." );
      $formHint.toggleClass( "congress-form-success", false );
      $formHint.toggleClass( "congress-form-error", false );

      $.post(
        ajaxurl,
        {
          action: action,
          states: stateString,
          "_wpnonce": formData.get( "_wpnonce" )
        },
        ( stateResults ) => {

          const stateErrors = [];

          for ( const stateRow of states ) {
            if ( stateResults.includes( stateRow.getStateCode() ) ) {
              this._actions[action]( stateRow );
            } else {
              stateErrors.push( stateRow );
            }
          }

          if ( 0 === stateErrors.length ) {
            $formHint.text( "Success!" );
            $formHint.toggleClass( "congress-form-success", true );
            $formHint.toggleClass( "congress-form-error", false );
          } else {
            const stateFailString = stateErrors
              .map( ( stateRow ) => stateRow.getStateCode() )
              .join( "," );
            $formHint.text( "Failed to apply action to: " + stateFailString );
            $formHint.toggleClass( "congress-form-success", false );
            $formHint.toggleClass( "congress-form-error", true );
          }

        })
        .fail( ({ responseJSON }) => {

          $formHint.text( responseJSON.error );
          $formHint.toggleClass( "congress-form-success", false );
          $formHint.toggleClass( "congress-form-error", true );

        });
    }

    /**
     * Activates the state.
     *
     * @param {StateRow} stateRow
     */
    activateState( stateRow ) {
      stateRow.toggleStatus( true );
      stateRow.toggleDetails( true );
    }

    /**
     * Deactivates the state.
     *
     * @param {StateRow} stateRow
     */
    deactivateState( stateRow ) {
      stateRow.toggleStatus( false );
    }

    /**
     * Enable federal syncing.
     *
     * @param {StateRow} stateRow
     */
    enableFederalSync( stateRow ) {
      stateRow.toggleFederalSync( true );
    }

    /**
     * Disable federal syncing.
     *
     * @param {StateRow} stateRow
     */
    disableFederalSync( stateRow ) {
      stateRow.toggleFederalSync( false );
    }

    /**
     * Enable state syncing.
     *
     * @param {StateRow} stateRow
     */
    enableStateSync( stateRow ) {
      stateRow.toggleStateSync( true );
    }

    /**
     * Disable state syncing.
     *
     * @param {StateRow} stateRow
     */
    disableStateSync( stateRow ) {
      stateRow.toggleStateSync( false );
    }
  }

  $( function() {
    const table = new StateTable();

    $( "#congress-state-search" ).on( "input", function( evt ) {

      const searchTerm = evt.target.value.toLowerCase();

      if ( "" === searchTerm ) {

        table.filter( () => true );

      } else {

        table.filter( ( stateRow ) => {
          const stateName = stateRow.getStateName().toLowerCase();
          if ( -1 !== stateName.search( searchTerm ) ) {
            return true;
          }
          return false;
        });

      }

    });

    $( "#congress-default-sync-email-form" )[0].email.oninput = () => {
      const $formHint = $( "#congress-default-sync-email-hint" );
      $formHint.text( "" );
    };

    $( "#congress-default-sync-email-form" ).on( "submit", function( evt ) {
      evt.preventDefault();

      const formData = new FormData( evt.target );

      const $formHint = $( "#congress-default-sync-email-hint" );
      $formHint.text( "Loading..." );
      $formHint.toggleClass( "congress-form-success", false );
      $formHint.toggleClass( "congress-form-error", false );

      $.post(
        ajaxurl,
        {
          action: evt.target.getAttribute( "action" ),
          email: formData.get( "email" ),
          "_wpnonce": formData.get( "_wpnonce" )
        },
        ( email ) => {
          evt.target.email.value = email;
          $formHint.text( "Success!" );
          $formHint.toggleClass( "congress-form-success", true );
          $formHint.toggleClass( "congress-form-error", false );
        })
        .fail( function({ responseJSON }) {
          $formHint.text( responseJSON.error );
          $formHint.toggleClass( "congress-form-success", false );
          $formHint.toggleClass( "congress-form-error", true );
        });
    });

  });
}( jQuery ) );
