import React from 'react';
import PropTypes from 'prop-types';

import DateTime from '../datetime';
import moment from 'moment';
import 'moment/min/locales';
import { FIELD_COMPONENT_BASE_PROPS } from 'dfv/src/config/prop-types';

const DateField = ( props ) => {
	const {
		fieldConfig = {},
	} = props;

	// Process the field config so that properties prefixed with "date_"
	// are changed to "datetime_".
	const fieldConfigEntries = Object.entries( fieldConfig ).filter(
		( entry ) => ! entry[ 0 ].startsWith( 'date_' )
	);

	const newConfig = {
		...Object.fromEntries( fieldConfigEntries ),
		datetime_allow_empty: fieldConfig.date_allow_empty,
		datetime_date_format_moment_js: fieldConfig.date_format_moment_js,
		datetime_format: fieldConfig.date_format,
		datetime_format_custom: fieldConfig.date_format_custom,
		datetime_format_custom_js: fieldConfig.date_format_custom_js,
		datetime_html5: fieldConfig.date_html5,
		datetime_type: fieldConfig.date_type,
		datetime_year_range_custom: fieldConfig.date_year_range_custom,
	};

	const userLocale = window?.podsDFVConfig?.userLocale ?? 'en';

	return (
		<DateTime
			{ ...props }
			fieldConfig={ newConfig }
			locale={ userLocale }
		/>
	);
};

DateField.propTypes = {
	...FIELD_COMPONENT_BASE_PROPS,
	value: PropTypes.string,
};

export default DateField;
