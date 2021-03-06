<?php
	define('TTRSS_SESSION_NAME', 'ttrss_m_sid');

	function render_feeds_list($link) {

		$tags = $_GET["tags"];

		print "<div id=\"heading\">";

		if ($tags) {
			print __("Tags")."<span id=\"headingAddon\">
				(<a href=\"index.php\">".__("View feeds")."</a>, ";
		} else {
			print __("Feeds")." <span id=\"headingAddon\">
				(<a href=\"index.php?tags=1\">".__("View tags")."</a>, ";
		}

		print "<a href=\"index.php?go=sform\">".__("Search")."</a>, ";

		print "<a href=\"logout.php\">".__("Logout")."</a>)</span>";
		print "</div>";

		print "<ul class=\"feedList\">";

		$owner_uid = $_SESSION["uid"];

		if (!$tags) {

			/* virtual feeds */

			if (get_pref($link, 'ENABLE_FEED_CATS')) {

				$collapsed = get_pref($link, "_COLLAPSED_SPECIAL");

				if ($collapsed == "t" || $collapsed == "1") {
					$holder_class = "invisible";
					$ellipsis = "...";
				} else {
					$holder_class = "feedCatHolder";
					$ellipsis = "";
				}

				$tmp_category = __("Special");

				print "<li class=\"feedCat\">
					<a href=\"?subop=tc&id=-1\">$tmp_category</a>$ellipsis
						</li>";

				print "<li class=\"$holder_class\"><ul class=\"feedCatList\">";
			}

			foreach (array(-4, -3, -1, -2, 0) as $i) {
				printMobileFeedEntry($i, "virt", false, false, 
					false, $link);
			}

			if (get_pref($link, 'ENABLE_FEED_CATS')) {
				print "</ul>";
			}

	
				$result = db_query($link, "SELECT id,caption FROM					
					ttrss_labels2 WHERE owner_uid = '$owner_uid' ORDER by caption");

				if (db_num_rows($result) > 0) {
					if (get_pref($link, 'ENABLE_FEED_CATS')) {

						$collapsed = get_pref($link, "_COLLAPSED_LABELS");

						if ($collapsed == "t" || $collapsed == "1") {
							$holder_class = "invisible";
							$ellipsis = "...";
						} else {
							$holder_class = "feedCatHolder";
							$ellipsis = "";
						}

						$tmp_category = __("Labels");

						print "<li class=\"feedCat\">
							<a href=\"?subop=tc&id=-2\">$tmp_category</a>$ellipsis
								</li>";

						print "<li class=\"$holder_class\"><ul class=\"feedCatList\">";
					} else {
//						print "<li><hr></li>";
					}
				}
		
				while ($line = db_fetch_assoc($result)) {
	
					$count = getFeedUnread($link, -$line["id"]-11);
	
					$class = "label";
	
					printMobileFeedEntry(-$line["id"]-11, 
						$class, $line["caption"], $count, false, $link);
		
				}

				if (db_num_rows($result) > 0) {
					if (get_pref($link, 'ENABLE_FEED_CATS')) {
						print "</ul>";
					}
				}


			if (get_pref($link, 'ENABLE_FEED_CATS')) {
				$order_by_qpart = "category,title";
			} else {
				$order_by_qpart = "title";
			}

			$result = db_query($link, "SELECT ttrss_feeds.*,
				".SUBSTRING_FOR_DATE."(last_updated,1,19) AS last_updated_noms,
				(SELECT COUNT(id) FROM ttrss_entries,ttrss_user_entries
					WHERE feed_id = ttrss_feeds.id AND 
					ttrss_user_entries.ref_id = ttrss_entries.id AND
					owner_uid = '$owner_uid') AS total,
				(SELECT COUNT(id) FROM ttrss_entries,ttrss_user_entries
					WHERE feed_id = ttrss_feeds.id AND unread = true
						AND ttrss_user_entries.ref_id = ttrss_entries.id
						AND owner_uid = '$owner_uid') as unread,
				cat_id,last_error,
				ttrss_feed_categories.title AS category,
				ttrss_feed_categories.collapsed	
				FROM ttrss_feeds LEFT JOIN ttrss_feed_categories 
					ON (ttrss_feed_categories.id = cat_id)				
				WHERE 
					ttrss_feeds.owner_uid = '$owner_uid'
				ORDER BY $order_by_qpart"); 

			$actid = $_GET["actid"];
	
			/* real feeds */
	
			$lnum = 0;
	
			$category = "";

			while ($line = db_fetch_assoc($result)) {
				if (get_pref($link, 'HIDE_READ_FEEDS') && (int)$line['unread']==0) {
					continue;
				}

				$feed = db_unescape_string($line["title"]);
				$feed_id = $line["id"];	  
	
				$subop = $_GET["subop"];
				
				$total = $line["total"];
				$unread = $line["unread"];

				$rtl_content = sql_bool_to_bool($line["rtl_content"]);

				if ($rtl_content) {
					$rtl_tag = "dir=\"RTL\"";
				} else {
					$rtl_tag = "";
				}

				$cat_id = $line["cat_id"];

				$tmp_category = $line["category"];

				if (!$tmp_category) {
					$tmp_category = "Uncategorized";
				}
				
	//			$class = ($lnum % 2) ? "even" : "odd";

				if ($line["last_error"]) {
					$class = "error";
				} else {
					$class = "feed";
				}
	
				if ($category != $tmp_category && get_pref($link, 'ENABLE_FEED_CATS')) {
				
					if ($category) {
						print "</ul></li>";
					}
				
					$category = $tmp_category;

					$collapsed = $line["collapsed"];

					// workaround for NULL category
					if ($category == "Uncategorized") {
						$collapsed = get_pref($link, "_COLLAPSED_UNCAT");
					}

					if ($collapsed == "t" || $collapsed == "1") {
						$holder_class = "invisible";
						$ellipsis = "...";
					} else {
						$holder_class = "feedCatHolder";
						$ellipsis = "";
					}

					if ($cat_id) {
						$cat_id_qpart = "cat_id = '$cat_id'";
					} else {
						$cat_id_qpart = "cat_id IS NULL";
					}

					$cat_id = sprintf("%d", $cat_id);
					$cat_unread = getCategoryUnread($link, $cat_id);

					if ($cat_unread > 0) {
						$catctr_class = "";
					} else {
						$catctr_class = "invisible";
					}

					print "<li class=\"feedCat\">
						<a href=\"?subop=tc&id=$cat_id\">$tmp_category</a>
							<a href=\"?go=vf&id=$cat_id&cat=true\">
								<span class=\"$catctr_class\">($cat_unread)$ellipsis</span>
							</a></li>";

					print "<li id=\"feedCatHolder\" class=\"$holder_class\">
						<ul class=\"feedCatList\">";
				}
	
				printMobileFeedEntry($feed_id, $class, $feed, $unread, 
					false, $link, $rtl_content);
	
				++$lnum;
			}

		} else {
			// tags

			$result = db_query($link, "SELECT tag_name,SUM((SELECT COUNT(int_id) 
				FROM ttrss_user_entries WHERE int_id = post_int_id 
					AND unread = true)) AS count FROM ttrss_tags 
				WHERE owner_uid = '".$_SESSION['uid']."' GROUP BY tag_name ORDER BY tag_name");

			$tags = array();
	
			while ($line = db_fetch_assoc($result)) {
				$tags[$line["tag_name"]] += $line["count"];
			}
	
			foreach (array_keys($tags) as $tag) {
	
				$unread = $tags[$tag];
	
				$class = "tag";
	
				printMobileFeedEntry($tag, $class, $tag, $unread, 
					"../images/tag.png", $link);
	
			} 

			
		}	
	}

	function printMobileFeedEntry($feed_id, $class, $feed_title, $unread, $icon_file, $link,
		$rtl_content = false) {

		if (!$feed_title) $feed_title = getFeedTitle($link, $feed_id, false);
		if (!$unread) $unread = getFeedUnread($link, $feed_id);	

		if ($unread > 0) $class .= "Unread";

		if (!$icon_file) $icon_file = "../../" . getFeedIcon($feed_id);

		if (file_exists($icon_file) && filesize($icon_file) > 0) {
				$feed_icon = "<img src=\"$icon_file\">";
		} else {
			$feed_icon = "<img src=\"../../images/blank_icon.gif\">";
		}

		if ($rtl_content) {
			$rtl_tag = "dir=\"rtl\"";
		} else {
			$rtl_tag = "dir=\"ltr\"";
		}

		$feed = "<a href=\"?go=vf&id=$feed_id\">$feed_title</a>";

		print "<li class=\"$class\">";
		print "$feed_icon";
		print "<span $rtl_tag>$feed</span> ";

		if ($unread != 0) {
			print "<span $rtl_tag>($unread)</span>";
		}
		
		print "</li>";

	}

	function render_headlines($link) {

		$feed = db_escape_string($_GET["id"]);
		$limit = db_escape_string($_GET["limit"]);
		$view_mode = db_escape_string($_GET["viewmode"]);
		$cat_view = db_escape_string($_GET["cat"]);
		$subop = $_GET["subop"];
		$catchup_op = $_GET["catchup_op"];

		if (!$view_mode) {
			if ($_SESSION["mobile:viewmode"]) {
				$view_mode = $_SESSION["mobile:viewmode"];
			} else {			
				$view_mode = "adaptive";
			}
		}

		$_SESSION["mobile:viewmode"] = $view_mode;

		if (!$limit) $limit = 30;
		if (!$feed) $feed = 0;

		if (preg_match("/^-?[0-9][0-9]*$/", $feed) != false) {

			$result = db_query($link, "SELECT rtl_content FROM ttrss_feeds
				WHERE id = '$feed' AND owner_uid = " . $_SESSION["uid"]);

			if (db_num_rows($result) == 1) {
				$rtl_content = sql_bool_to_bool(db_fetch_result($result, 0, "rtl_content"));
			} else {
				$rtl_content = false;
			}

			if ($rtl_content) {
				$rtl_tag = "dir=\"RTL\"";
			} else {
				$rtl_tag = "";
			}
		} else {
			$rtl_content = false;
			$rtl_tag = "";
		}

		print "<div id=\"headlines\" $rtl_tag>";

		if ($subop == "ForceUpdate" && sprintf("%d", $feed) > 0) {
			update_generic_feed($link, $feed, $cat_view, true);
		}

		if ($subop == "MarkAllRead" || $catchup_op == "feed")  {
			catchup_feed($link, $feed, $cat_view);
		}

		if ($catchup_op == "selection") {
			if (is_array($_GET["sel_ids"])) {
				$ids_to_mark = array_keys($_GET["sel_ids"]);
				if ($ids_to_mark) {
					foreach ($ids_to_mark as $id) {
						db_query($link, "UPDATE ttrss_user_entries SET 
							unread = false,last_read = NOW()
							WHERE ref_id = '$id' AND owner_uid = " . $_SESSION["uid"]);
					}
				}
			}
		}

		if ($subop == "MarkPageRead" || $catchup_op == "page") {
			$ids_to_mark = $_SESSION["last_page_ids.$feed"];

			if ($ids_to_mark) {

				foreach ($ids_to_mark as $id) {
					db_query($link, "UPDATE ttrss_user_entries SET 
						unread = false,last_read = NOW()
						WHERE ref_id = '$id' AND owner_uid = " . $_SESSION["uid"]);
				}
			}
		}


		/// START /////////////////////////////////////////////////////////////////////////////////

		$search = db_escape_string($_GET["query"]);
		$search_mode = db_escape_string($_GET["search_mode"]);
		$match_on = db_escape_string($_GET["match_on"]);

		if (!$match_on) {
			$match_on = "both";
		}

		$real_offset = $offset * $limit;

		if ($_GET["debug"]) $timing_info = print_checkpoint("H0", $timing_info);

		$qfh_ret = queryFeedHeadlines($link, $feed, $limit, $view_mode, $cat_view, 
			$search, $search_mode, $match_on, false, $real_offset);

		if ($_GET["debug"]) $timing_info = print_checkpoint("H1", $timing_info);

		$result = $qfh_ret[0];
		$feed_title = $qfh_ret[1];
		$feed_site_url = $qfh_ret[2];
		$last_error = $qfh_ret[3];
		
		/// STOP //////////////////////////////////////////////////////////////////////////////////

		if (!$result) {
			print "<div align='center'>".
				__("Could not display feed (query failed). Please check label match syntax or local configuration.").
				"</div>";
			return;
		}

		print "<div id=\"heading\">";
		#		if (!$cat_view && file_exists("../icons/$feed.ico") && filesize("../icons/$feed.ico") > 0) {
			#			print "<img class=\"feedIcon\" src=\"../icons/$feed.ico\">";
			#		}
		
		print "$feed_title <span id=\"headingAddon\">(";
		print "<a href=\"index.php\">".__("Back")."</a>, ";
		print "<a href=\"index.php?go=sform&aid=$feed&ic=$cat_view\">".__("Search")."</a>, ";
		print "<a href=\"index.php?go=vf&id=$feed&subop=ForceUpdate\">".__("Update")."</a>";

#		print "Mark as read: ";
#		print "<a href=\"index.php?go=vf&id=$feed&subop=MarkAsRead\">Page</a>, ";
#		print "<a href=\"index.php?go=vf&id=$feed&subop=MarkAllRead\">Feed</a>";

		print ")</span>";

		print "&nbsp;" . __('View:');

		print "<form style=\"display : inline\" method=\"GET\" action=\"index.php\">";

		/* print "<select name=\"viewmode\">
			<option selected value=\"adaptive\"> " . __('Adaptive') . "</option>
			<option value=\"all_articles\">" . __('All Articles') . "</option>
			<option value=\"marked\">" . __('Starred') . "</option>
			<option value=\"unread\">" . __('Unread') . "</option>
			</select>"; */

		$sel_values = array(
			"adaptive" => __("Adaptive"),
			"all_articles" => __("All Articles"),
			"unread" => __("Unread"),
			"marked" => __("Starred"));

		print_select_hash("viewmode", $view_mode, $sel_values);

		print "<input type=\"hidden\" name=\"id\" value=\"$feed\">
		<input type=\"hidden\" name=\"cat\" value=\"$cat_view\">
		<input type=\"hidden\" name=\"go\" value=\"vf\">
		<input type=\"submit\" value=\"".__('Refresh')."\">";
		print "</form>";

		print "</div>";
	
		if (db_num_rows($result) > 0) {

			print "<form method=\"GET\" action=\"index.php\">";
			print "<input type=\"hidden\" name=\"go\" value=\"vf\">";
			print "<input type=\"hidden\" name=\"id\" value=\"$feed\">";
			print "<input type=\"hidden\" name=\"cat\" value=\"$cat_view\">";

			print "<ul class=\"headlines\" id=\"headlines\">";

			$page_art_ids = array();
			
			$lnum = 0;
	
			error_reporting (DEFAULT_ERROR_LEVEL);
	
			$num_unread = 0;
	
			while ($line = db_fetch_assoc($result)) {

				$class = ($lnum % 2) ? "even" : "odd";
	
				$id = $line["id"];
				$feed_id = $line["feed_id"];

				array_push($page_art_ids, $id);
	
				if ($line["last_read"] == "" && 
						($line["unread"] != "t" && $line["unread"] != "1")) {
	
					$update_pic = "<img id='FUPDPIC-$id' src=\"images/updated.png\" 
						alt=\"".__("Updated")."\">";
				} else {
					$update_pic = "<img id='FUPDPIC-$id' src=\"images/blank_icon.gif\" 
						alt=\"".__("Updated")."\">";
				}
	
				if ($line["unread"] == "t" || $line["unread"] == "1") {
					$class .= "Unread";
					++$num_unread;
					$is_unread = true;
				} else {
					$is_unread = false;
				}
	
				if ($line["marked"] == "t" || $line["marked"] == "1") {
					$marked_pic = "<img alt=\"S\" class='marked' src=\"../../images/mark_set.png\">";
				} else {
					$marked_pic = "<img alt=\"s\" class='marked' src=\"../../images/mark_unset.png\">";
				}

				if ($line["published"] == "t" || $line["published"] == "1") {
					$published_pic = "<img alt=\"P\" class='marked' src=\"../../images/pub_set.gif\">";
				} else {
					$published_pic = "<img alt=\"p\" class='marked' src=\"../../images/pub_unset.gif\">";
				}

				$content_link = "<a href=\"?go=view&id=$id&cat=$cat_view&ret_feed=$feed&feed=$feed_id\">" .
					$line["title"] . "</a>";

				$updated_fmt = make_local_datetime($link, $line['updated'], false);

				print "<li class='$class' id=\"HROW-$id\">";

				print "<input type=\"checkbox\" name=\"sel_ids[$id]\"
				  	id=\"HSCB-$id\" onchange=\"toggleSelectRow(this, $id)\">";

				print "<a href=\"?go=vf&id=$feed&ts=$id&cat=$cat_view\">$marked_pic</a>";
				print "<a href=\"?go=vf&id=$feed&tp=$id&cat=$cat_view\">$published_pic</a>";

				print $content_link;
	
				if ($line["feed_title"]) {			
					print " (<a href='?go=vf&id=$feed_id'>".
							$line["feed_title"]."</a>)";
				}

				print "<span class='hlUpdated'> ($updated_fmt)</span>";

				print "</li>";

	
				++$lnum;
			}

			print "</ul>";

			print "<div class='footerAddon'>";

			$_SESSION["last_page_ids.$feed"] = $page_art_ids;

/*			print "<a href=\"index.php?go=vf&id=$feed&subop=MarkPageRead\">Page</a>, ";
			print "<a href=\"index.php?go=vf&id=$feed&subop=MarkAllRead\">Feed</a></div>"; */

			print "Select: 
				<a href=\"javascript:selectHeadlines(1)\">".__("All")."</a>,
				<a href=\"javascript:selectHeadlines(2)\">".__("Unread")."</a>,
				<a href=\"javascript:selectHeadlines(3)\">".__("None")."</a>,
				<a href=\"javascript:selectHeadlines(4)\">".__("Invert")."</a>";

			print " ";

			print "<select name=\"catchup_op\">
				<option value=\"selection\">".__("Selection")."</option>
				<option value=\"page\">".__("Page")."</option>
				<option value=\"feed\">".__("Entire feed")."</option>
			</select>
			<input type=\"hidden\" name=\"cat\" value=\"$cat_view\">
			<input type=\"submit\" value=\"".__("Mark as read")."\">";

			print "</form>";

		} else {
			print "<div align='center'>No articles found.</div>";
		}

	}

	function render_article($link) {

		$id = db_escape_string($_GET["id"]);
		$feed_id = db_escape_string($_GET["feed"]);
		$ret_feed_id = db_escape_string($_GET["ret_feed"]);
		$cat_view = db_escape_string($_GET["cat"]);

		$result = db_query($link, "SELECT rtl_content FROM ttrss_feeds
			WHERE id = '$feed_id' AND owner_uid = " . $_SESSION["uid"]);

		if (db_num_rows($result) == 1) {
			$rtl_content = sql_bool_to_bool(db_fetch_result($result, 0, "rtl_content"));
		} else {
			$rtl_content = false;
		}

		if ($rtl_content) {
			$rtl_tag = "dir=\"RTL\"";
			$rtl_class = "RTL";
		} else {
			$rtl_tag = "";
			$rtl_class = "";
		}

		$result = db_query($link, "UPDATE ttrss_user_entries 
			SET unread = false,last_read = NOW() 
			WHERE ref_id = '$id' AND feed_id = '$feed_id' AND owner_uid = " . $_SESSION["uid"]);

		$result = db_query($link, "SELECT title,link,content,feed_id,comments,int_id,
			marked,published,
			".SUBSTRING_FOR_DATE."(updated,1,16) as updated,
			(SELECT icon_url FROM ttrss_feeds WHERE id = feed_id) as icon_url,
			num_comments,
			author
			FROM ttrss_entries,ttrss_user_entries
			WHERE	id = '$id' AND ref_id = id");

		if ($result) {

			$line = db_fetch_assoc($result);

			$num_comments = $line["num_comments"];
			$entry_comments = "";

			if ($num_comments > 0) {
				if ($line["comments"]) {
					$comments_url = $line["comments"];
				} else {
					$comments_url = $line["link"];
				}
				$entry_comments = "<a href=\"$comments_url\">$num_comments comments</a>";
			} else {
				if ($line["comments"] && $line["link"] != $line["comments"]) {
					$entry_comments = "<a href=\"".$line["comments"]."\">comments</a>";
				}				
			}

			$tmp_result = db_query($link, "SELECT DISTINCT tag_name FROM
				ttrss_tags WHERE post_int_id = " . $line["int_id"] . "
				ORDER BY tag_name");
	
			$tags_str = "";
			$f_tags_str = "";

			$num_tags = 0;

			while ($tmp_line = db_fetch_assoc($tmp_result)) {
				$num_tags++;
				$tag = $tmp_line["tag_name"];				
				$tag_str = "<a href=\"?go=vf&id=$tag\">$tag</a>, "; 
				$tags_str .= $tag_str;
			}

			$tags_str = preg_replace("/, $/", "", $tags_str);

			$parsed_updated = date(get_pref($link, 'SHORT_DATE_FORMAT'), 
				strtotime($line["updated"]));

			print "<div id=\"heading\">";

			#			if (file_exists("../icons/$feed_id.ico") && filesize("../icons/$feed_id.ico") > 0) {
				#				print "<img class=\"feedIcon\" src=\"../icons/$feed_id.ico\">";
				#			}

			if (!$cat_view) {
				$feed_title = getFeedTitle($link, $ret_feed_id);
			} else {
				$feed_title = getCategoryTitle($link, $ret_feed_id);
				$feed_title_native = getFeedTitle($link, $feed_id);
			}

			if ($feed_title_native) {
				$feed_link = "<a href=\"index.php?go=vf&id=$feed_id\">$feed_title_native</a>";
				$feed_link .= " in <a href=\"index.php?go=vf&id=$ret_feed_id&cat=$cat_view\">
					$feed_title</a>";
			} else {
				$feed_link = "<a href=\"index.php?go=vf&id=$ret_feed_id\">$feed_title</a>";
			}

			$feedlist = "<a href=\"index.php\">".__('Back to feedlist')."</a>";

			print "<a href=\"" . $line["link"] . "\">" . 
				truncate_string($line["title"], 30) . "</a>";
			print " <span id=\"headingAddon\">$parsed_updated ($feed_link, $feedlist)</span>";
			print "</div>";

			if ($num_tags > 0) {
				print "<div class=\"postTags\">".__("Tags:")." $tags_str</div>";
			}

			if ($line["marked"] == "t" || $line["marked"] == "1") {
				$marked_pic = "<img class='marked' src=\"../../images/mark_set.png\">";
			} else {
				$marked_pic = "<img class='marked' src=\"../../images/mark_unset.png\">";
			}

			if ($line["published"] == "t" || $line["published"] == "1") {
				$published_pic = "<img class='marked' src=\"../../images/pub_set.gif\">";
			} else {
				$published_pic = "<img class='marked' src=\"../../images/pub_unset.gif\">";
			}


			print "<div class=\"postStarOps\">";
			print "<a title=\"".__('Toggle starred')."\"href=\"?go=view&id=$id&ret_feed=$ret_feed_id&feed=$feed_id&sop=ts\">$marked_pic</a>";
			print "<a title=\"".__('Toggle published')."\" href=\"?go=view&id=$id&ret_feed=$ret_feed_id&feed=$feed_id&sop=tp\">$published_pic</a>";
			// Mark unread
			print "<a title=\"".__('Mark as unread')."\" href=\"?go=vf&id=$ret_feed_id&feed=$feed_id&sop=mu&aid=$id";
			if ($cat_view) { print "&cat=$cat_view"; }
			print "\"><img class='marked' src=\"../../images/art-set-unread.png\"></a>";
			print "</div>";

			print sanitize_rss($link, $line["content"], true);; 
		
		}

		print "</body></html>";
	}

	function render_search_form($link, $active_feed_id = false, $is_cat = false) {

		print "<div id=\"heading\">";

		print __("Search")." <span id=\"headingAddon\">
				(<a href=\"index.php\">".__("Go back")."</a>)</span></div>";

		print "<form method=\"GET\" action=\"index.php\" class=\"searchForm\">";

		print "<input type=\"hidden\" name=\"go\" value=\"vf\">";
		print "<input type=\"hidden\" name=\"id\" value=\"$active_feed_id\">";
		print "<input type=\"hidden\" name=\"cat\" value=\"$is_cat\">";

		print "<table><tr><td>".__('Search:')."</td><td>";
		print "<input name=\"query\"></td></tr>";

		print "<tr><td>".__('Where:')."</td><td>";
		
		print "<select name=\"search_mode\">
			<option value=\"all_feeds\">".__('All feeds')."</option>";
			
		$feed_title = getFeedTitle($link, $active_feed_id);

		if (!$is_cat) {
			$feed_cat_title = getFeedCatTitle($link, $active_feed_id);
		} else {
			$feed_cat_title = getCategoryTitle($link, $active_feed_id);
		}
			
		if ($active_feed_id && !$is_cat) {				
			print "<option selected value=\"this_feed\">$feed_title</option>";
		} else {
			print "<option disabled>".__('This feed')."</option>";
		}

		if ($is_cat) {
		  	$cat_preselected = "selected";
		}

		if (get_pref($link, 'ENABLE_FEED_CATS') && ($active_feed_id > 0 || $is_cat)) {
			print "<option $cat_preselected value=\"this_cat\">$feed_cat_title</option>";
		} else {
			//print "<option disabled>".__('This category')."</option>";
		}

		print "</select></td></tr>"; 

		print "<tr><td>".__('Match on:')."</td><td>";

		$search_fields = array(
			"title" => __("Title"),
			"content" => __("Content"),
			"both" => __("Title or content"));

		print_select_hash("match_on", 3, $search_fields); 
				
		print "</td></tr></table>";

		print "<input type=\"submit\" value=\"".__('Search')."\">";

		print "</form>";

		print "</div>";
	}

	function toggleMarked($link, $ts_id) {
		$result = db_query($link, "UPDATE ttrss_user_entries SET marked = NOT marked
			WHERE ref_id = '$ts_id' AND owner_uid = " . $_SESSION["uid"]);
	}

	function togglePublished($link, $tp_id) {
		$result = db_query($link, "UPDATE ttrss_user_entries SET published = NOT published
			WHERE ref_id = '$tp_id' AND owner_uid = " . $_SESSION["uid"]);
	}

	function markUnread($link, $mu_id) {
		$result = db_query($link, "UPDATE ttrss_user_entries SET unread = true 
			WHERE ref_id = '$mu_id' AND owner_uid = " . $_SESSION["uid"]);
	}

?>
