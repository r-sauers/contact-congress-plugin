/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

import React, {useEffect, useState} from "react";
import PropTypes from "prop-types";

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, TextControl, ToggleControl, FormFileUpload, CustomSelectControl } from "@wordpress/components";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
  const { campaignID, campaignName } = attributes;
  const blockProps = useBlockProps();
  let [ options, setOptions ] = useState([]);

  useEffect( () => {
    jQuery.post(
      ajaxurl,
      {
        action: "get_campaign_names"
      },
      function( campaigns ) {
        let _options = [];
        for ( const campaign of campaigns ) {
          _options.push({
            "key": campaign.id,
            "name": campaign.name
          });
        }
        setOptions( _options );
      }
    );
  }, []);

  function className( ...classNames ) {
    let className = "";
    for ( let cn of classNames ) {
      className += `${blockProps.className}__${cn} `;
    }
    return {
      className: className
    };
  }

  function id( name ) {
    return {
      id: `${blockProps.id}__${name}`
    };
  }

  function htmlFor( name ) {
    return {
      htmlFor: `${blockProps.id}__${name}`
    };
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title={__( "Settings", "copyright-date-block" )}>
          <CustomSelectControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={__( "Campaign", "congress-form-block" )}
            value={{
              name: campaignName,
              key: campaignID
            }}
            options={options}
            onChange={( select ) => {
              setAttributes({
                campaignName: select.selectedItem.name,
                campaignID: parseInt( select.selectedItem.key )
              });
            }}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()}>
        <h3>Contact Your Representative</h3>
        <form action="">
          <div {...className( "form-group" )}>
            <label {...htmlFor( "name" )}>First Name: </label>
            <div {...className( "form-control" )}>
              <input type="text" name="name" {...id( "name" )} required disabled/>
            </div>

          </div>
          <div {...className( "form-group" )}>
            <label {...htmlFor( "email" )}>Email: </label>
            <div {...className( "form-control" )}>
              <input type="email" name="email" {...id( "email" )} required disabled/>
            </div>
          </div>
          <div {...className( "form-group" )}>
            <label {...htmlFor( "street-address" )}>Street Address: </label>
            <div {...className( "form-control" )}>
              <input type="text" name="streetAddress" {...id( "street-address" )} style={{display: "block"}} required disabled/>
              <div {...className( "form-info" )}>To find your representative</div>
            </div>
          </div>
          <div>
            <textarea name="emailBody" {...id( "email-body" )} style={{whiteSpace: "pre"}} disabled>Dear Representative...</textarea>
          </div>
          <div>
            <button type="submit" disabled>Send</button>
          </div>
        </form>
      </div>
    </>
  );
}

Edit.propTypes = {
  attributes: PropTypes.object,
  setAttributes: PropTypes.func
};
