<?php

// keep non-admins out
/*
if (! session_admin ()) {
	echo '<script language="javascript"><!--
		window.close ();
	// --></script>';
	exit;
}
*/

// settings stuff
global $cgi;

if (empty ($cgi->format)) {
	$cgi->format = 'html';
}

// get the path root from the imagechooser-path session variable,
// and if not then default to /pix.
$path = session_get ('imagechooser_path');
if (! $path) {
	$path = '/pix';
}

$data = array (
	'location' => false,
	'up' => false,
	'subfolders' => array (),
	'images' => array (),
	'name' => $cgi->name,
);

if (
		empty ($cgi->location) ||
		strpos ($cgi->location, $path . '/') !== 0 ||
		strstr ($cgi->location, '..') ||
		! @is_dir (site_docroot () . $cgi->location)
) {
	$data['location'] = $path;
} else {
	$data['location'] = $cgi->location;
	$up = preg_replace ('|/[^/]+$|', '', $data['location']);
	if (! empty ($up)) {
		$data['up'] = $up;
	}
}

// get all the data
page_title (intl_get ('Folder') . ': ' . $data['location']);

if (strpos ($data['location'], '/') !== 0) {
	$data['location'] = '/' . $data['location'];
}

$dir = site_docroot () . $data['location'];

if (! @is_dir ($dir)) {
	echo '<p>Error: Image folder doesn\'t exist</p>';
	echo '<p>Path: ' . $dir . '</p>';
	exit;
}
if ($dh = opendir ($dir)) {
	while (false !== ($file = readdir ($dh))) {
		if (strpos ($file, '.') === 0 || $file == 'CVS') {
			continue;
		}
		if (@is_dir ($dir . '/' . $file)) {
			$data['subfolders'][] = $file;
		} elseif (preg_match ('/\.(jpg|gif|png)$/i', $file)) {
			list ($w, $h) = @getimagesize (site_docroot () . $data['location'] . '/' . $file);
			$data['images'][] = array ('name' => $file, 'width' => $w, 'height' => $h);
		}
	}
}

function image_sort ($a, $b) {
	if ($a['name'] == $b['name']) {
		return 0;
	}
	return (strtolower ($a['name']) < strtolower ($b['name'])) ? -1 : 1;
}

sort ($data['subfolders']);
usort ($data['images'], 'image_sort');

if (! is_writeable (site_docroot () . $data['location'])) {
	$data['writeable'] = false;
} else {
	$data['writeable'] = true;
}

if (! session_allowed ('imagechooser_delete', 'rw', 'resource')) {
	$data['delete'] = false;
} else {
	$data['delete'] = true;
}

// show me the money
template_simple_register ('cgi', $cgi);
echo template_simple ('index.spt', $data);

exit;

?>