/**
 * Created by Pop Aurelian on 10-Jan-19.
 */

var TVD_SS = TVD_SS || {};
TVD_SS.util = TVD_SS.util || {};

TVD_SS.util.field_types = {
	0: 'text',
	1: 'address',
	2: 'phone',
	3: 'email',
	4: 'link',
	5: 'location'
};

TVD_SS.util.get_field_type_name = function(id) {
	return TVD_SS.util.field_types[id];
};