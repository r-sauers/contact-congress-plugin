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

  function className( classNames, inlineClassNames = []) {
    let className = "";
    for ( let cn of classNames ) {
      className += `${blockProps.className}__${cn} `;
    }
    for ( let cn of inlineClassNames ) {
      className += `${cn} `;
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

  const pfpTemplate = "data:image/svg+xml,<%3Fxml version=\"1.0\" encoding=\"utf-8\"%3F><svg viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path opacity=\"0.4\" d=\"M12 22.01C17.5228 22.01 22 17.5329 22 12.01C22 6.48716 17.5228 2.01001 12 2.01001C6.47715 2.01001 2 6.48716 2 12.01C2 17.5329 6.47715 22.01 12 22.01Z\" fill=\"%23292D32\"/><path d=\"M12 6.93994C9.93 6.93994 8.25 8.61994 8.25 10.6899C8.25 12.7199 9.84 14.3699 11.95 14.4299C11.98 14.4299 12.02 14.4299 12.04 14.4299C12.06 14.4299 12.09 14.4299 12.11 14.4299C12.12 14.4299 12.13 14.4299 12.13 14.4299C14.15 14.3599 15.74 12.7199 15.75 10.6899C15.75 8.61994 14.07 6.93994 12 6.93994Z\" fill=\"%23292D32\"/><path d=\"M18.7807 19.36C17.0007 21 14.6207 22.01 12.0007 22.01C9.3807 22.01 7.0007 21 5.2207 19.36C5.4607 18.45 6.1107 17.62 7.0607 16.98C9.7907 15.16 14.2307 15.16 16.9407 16.98C17.9007 17.62 18.5407 18.45 18.7807 19.36Z\" fill=\"%23292D32\"/></svg>";

  if ( ! campaignName ) {
    setAttributes({
      campaignName: "<Campaign_Name>"
    });
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
        <h3 {...className([], [ "wp-block-heading" ])}>Contact Your Representative</h3>
        <h4><strong>Step 1:</strong> Find Your Representatives</h4>
        <form
          {...id( "get-reps-form" )}
          action="get_reps"
          method="post"
        >
        <div {...className([ "form-group" ])}>
          <label {...htmlFor( "street-address" )}>Street Address: </label>
          <div {...className([ "form-control" ])}>
            <select
              {...id( "street-address" )}
              type="text" name="address"
              required>
            </select>
          </div>
      </div>
          <button type="submit" {...className([ "wide" ], [ "wp-element-button" ])}>Find</button>
        </form>

        <h4><strong>Step 2:</strong> Send Some Emails!</h4>
        <form action="" {...id( "email-form" )}>
          <div {...className([ "form-group" ])}>
            <label {...htmlFor( "first-name" ) }>First Name: </label>
            <div {...className([ "form-control" ])}>
              <input type="text" name="firstName" {...id( "first-name" ) } required/>
            </div>
          </div>
          <div {...className([ "form-group" ])}>
            <label {...htmlFor( "last-name" )}>Last Name: </label>
            <div {...className([ "form-control" ])}>
              <input type="text" name="lastName" {...id( "last-name" )} required/>
            </div>
          </div>
          <div {...className([ "form-group" ])}>
            <label {...htmlFor( "email" )}>Email: </label>
            <div {...className([ "form-control" ])}>
              <input type="email" name="email" {...id( "email" )} required/>
            </div>
          </div>
          <div {...className([ "form-group" ])}>
            <label {...htmlFor( "subject" )}>Subject: </label>
            <div {...className([ "form-control" ])}>
              <input type="text" name="subject" {...{value: `Support ${campaignName}`}} {...id( "subject" )}required/>
            </div>
          </div>
          <div>
            <textarea name="template" {...id( "email-template" )} style={{whiteSpace: "pre"}}>Dear Representative...</textarea>
          </div>
          <a
            {...id( "preview-toggle" )}
            {...className([ "preview-open", "preview-toggle" ])}
          >Show Preview</a>
          <div>
            <button type="submit" {...className([ "wide" ], [ "wp-element-button" ])} disabled>Send</button>
          </div>
          <ul {...id( "rep-container" )} style={{listStyleType: "none", padding: "0px"}}>
            <div {...className([ "rep-form" ])}>
              <img src={pfpTemplate} {...className([ "pfp" ])}/>
              <div {...className([ "rep-details" ])}>
                <div>
                  <span {...className([ "rep-title" ])}>Senator</span>
                  <span {...className([ "rep-first" ])}>Jane</span>
                  <span {...className([ "rep-last" ])}>Doe</span>
                </div>
                <div>
                  (<span {...className([ "rep-state" ])}>MN</span><span {...className([ "rep-district" ])}></span>)
                </div>
              </div>
              <button type="submit" className="wp-element-button">Send</button>
            </div>
          </ul>
        </form>
      </div>
    </>
  );
}

Edit.propTypes = {
  attributes: PropTypes.object,
  setAttributes: PropTypes.func
};
