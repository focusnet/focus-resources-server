<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file displays all the schemas being stored in this repository.
 */

$files = glob( '*.json');
sort($fiels, SORT_NATURAL);

$last_title = FALSE;
$last_type_id = FALSE;

// all errors silently discarded
foreach ($files as $f) {
	$content = file_get_contents($f);
	if ($content === FALSE) {
		continue;
	}
	
	$json = json_decode($content);
	if ($json === FALSE) {
		continue;
	}
	
	if (!isset($json->id) || !$json->id || !isset($json->title) || !$json->title) {
		continue;
	}
	
	$matches = array();
	if (!preg_match('|^(.*/)v\d+(\.\d+)?$|', $json->id, $matches)) {
		continue;
	}
	
	$type_id = $matches[1];
	if (!isset($types[$type_id])) {
		$types[$type_id] = array();
	}
	$types[$type_id][$json->id] = $content;
	
	// if we move to a new type, replace its id by the actual title
	// (we wait for the last version of the type, so we have the latest name displayed)
	if ($last_type_id && $type_id !== $last_type_id) {
		$types[$last_title] = $types[$last_type_id];
		unset($types[$last_type_id]);
	}
	$last_type_id = $type_id;
	$last_title = $json->title;
}

// at the end correct the last type being analysed 
$types[$last_title] = $types[$last_type_id];
unset($types[$last_type_id]);


?><!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>JSON Schemas</title>
	</head>

	<body>

		<h1>JSON Schemas</h1>
		
		<p>The following schemas are made available by this server.</p>
		
		<hr/>
		
		<?php foreach ($types as $name => $versions): ?>
			<h2><?php print $name; ?></h2>
			<ul>
				<?php foreach ($versions as $url => $code): ?>
					<li>
						<a href="<?php print $url; ?>"><?php print $url;?></a>
						<pre><?php print $code; ?></pre>
					</li>
				<?php endforeach; ?>
			</ul>
			<hr/>
		<?php endforeach; ?>
		
	</body>

</html>

