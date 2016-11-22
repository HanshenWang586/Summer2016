<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$db = new DatabaseQuery;
$db->execute('TRUNCATE listings_sitecitycat');
$rs = $db->execute('SELECT site_id FROM sites');

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("	SELECT c.city_id
							FROM ccl_cities2site c2s, listings_cities c
							WHERE c.city_id=c2s.city_id
							AND live=1
							AND site_id={$row['site_id']}");

		if ($rs_2->getNum() == 0)
		{
		// cities are unrestricted
		$rs_2 = $db->execute("	SELECT city_id
								FROM listings_cities
								WHERE live=1");
		}

		while ($row_2 = $rs_2->getRow())
		{
		$rs_3 = $db->execute("	SELECT category_id
								FROM ccl_categories2site
								WHERE site_id={$row['site_id']}");

			if ($rs_3->getNum() == 0)
			{
			// categories are unrestricted
			$rs_3 = $db->execute("	SELECT category_id
									FROM listings_categories
									WHERE live=1");
			}

			while ($row_3 = $rs_3->getRow())
			{
			$category_ids = array();
			$cat = new Category($row_3['category_id']);
			$category_ids = $cat->getAllSubCategoryIDs();

				foreach ($category_ids as $category_id)
				{
				$db->execute("	INSERT INTO listings_sitecitycat (	site_id,
																	city_id,
																	category_id)
								VALUES (	{$row['site_id']},
											{$row_2['city_id']},
											$category_id)");
				}
			}
		}
	}

// tallies
$rs = $db->execute("SELECT * FROM listings_sitecitycat");

	while ($row = $rs->getRow())
	{
	$category_ids = array();
	$cat = new Category($row['category_id']);
	$category_ids = $cat->getAllSubCategoryIDs();

	$rs_2 = $db->execute("	SELECT COUNT(*) AS tally
							FROM listings_data d, listings_i2c i2c
							WHERE d.listing_id=i2c.listing_id
							AND d.status=1
							AND city_id={$row['city_id']}
							AND category_id IN (".implode(', ', $category_ids).")");

	$row_2 = $rs_2->getRow();
	$db->execute("	UPDATE listings_sitecitycat
					SET tally={$row_2['tally']}
					WHERE scc_id={$row['scc_id']}");
	}

//output
/*
$body .= "category tallies include subcategory items<br />";

$rs = $db->execute("SELECT site_name, city_en, category_en, scc.tally
					FROM listings_sitecitycat scc, listings_cities c, listings_categories cc, sites s
					WHERE scc.site_id=s.site_id
					AND c.city_id=scc.city_id
					AND scc.category_id=cc.category_id
					ORDER BY site_name, city_en, category_en");

	while ($row = $rs->getRow())
	{
	$body .= '<pre>'.print_r($row, true).'</pre>';
	}
*/

$db->execute("DELETE FROM listings_sitecitycat WHERE tally=0");
echo 'done';
?>