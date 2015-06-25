--
-- This file is part of the focus-resources-server package.
--
-- For the full copyright and license information, please view the LICENSE
-- file that was distributed with this source code.
--

-- Samples
DROP TABLE IF EXISTS samples;

CREATE TABLE IF NOT EXISTS samples (
	id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	url VARCHAR(245) NOT NULL,
	version INT(3) UNSIGNED NOT NULL,
	type VARCHAR(255) NOT NULL,
	owner VARCHAR(255) NOT NULL,
	creation_datetime DATETIME NOT NULL,
	editor VARCHAR(255),
	edition_datetime DATETIME,
	active BOOLEAN NOT NULL DEFAULT TRUE,
	data LONGTEXT NOT NULL,

	CONSTRAINT idx_resource_url UNIQUE INDEX (url, version),
	INDEX idx_type (type)
);

