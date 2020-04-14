<?php

/*
 * OIDplus 2.0
 * Copyright 2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!defined('IN_OIDPLUS')) die();

class OIDplusGui {

	private static $crudCounter = 0;

	protected static function objDescription($html) {
		// We allow HTML, but no hacking
		$html = anti_xss($html);

		return trim_br($html);
	}

	protected static function showCrud($parent='oid:') {
		$items_total = 0;
		$items_hidden = 0;

		$objParent = OIDplusObject::parse($parent);
		$parentNS = $objParent::ns();

		$result = OIDplus::db()->query("select o.*, r.ra_name " .
		                               "from ".OIDPLUS_TABLENAME_PREFIX."objects o " .
		                               "left join ".OIDPLUS_TABLENAME_PREFIX."ra r on r.email = o.ra_email " .
		                               "where parent = ? " .
		                               "order by ".OIDplus::db()->natOrder('id'), array($parent));
		$rows = array();
		if ($parentNS == 'oid') {
			$one_weid_available = $objParent->isWeid(true);
			while ($row = $result->fetch_object()) {
				$obj = OIDplusObject::parse($row->id);
				$rows[] = array($obj,$row);
				if (!$one_weid_available) {
					if ($obj->isWeid(true)) $one_weid_available = true;
				}
			}
		} else {
			$one_weid_available = false;
			while ($row = $result->fetch_object()) {
				$obj = OIDplusObject::parse($row->id);
				$rows[] = array($obj,$row);
			}
		}

		$output = '';
		$output .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
		$output .= '<table class="table table-bordered table-striped">';
		$output .= '	<tr>';
		$output .= '	     <th>ID'.(($parentNS == 'gs1') ? ' (without check digit)' : '').'</th>';
		if ($parentNS == 'oid') {
			if ($one_weid_available) $output .= '	     <th>WEID</th>';
			$output .= '	     <th>ASN.1 IDs (comma sep.)</th>';
			$output .= '	     <th>IRI IDs (comma sep.)</th>';
		}
		$output .= '	     <th>RA</th>';
		$output .= '	     <th>Comment</th>';
		if ($objParent->userHasWriteRights()) {
			$output .= '	     <th>Hide</th>';
			$output .= '	     <th>Update</th>';
			$output .= '	     <th>Delete</th>';
		}
		$output .= '	     <th>Created</th>';
		$output .= '	     <th>Updated</th>';
		$output .= '	</tr>';

		foreach ($rows as list($obj,$row)) {
			$items_total++;
			if (!$obj->userHasReadRights()) {
				$items_hidden++;
				continue;
			}

			$show_id = $obj->crudShowId($objParent);

			$asn1ids = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$asn1ids[] = $row2['name'];
			}

			$iris = array();
			$res2 = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."iri where oid = ? order by lfd", array($row->id));
			while ($row2 = $res2->fetch_array()) {
				$iris[] = $row2['name'];
			}
			
			$date_created = explode(' ', $row->created)[0] == '0000-00-00' ? '' : explode(' ', $row->created)[0];
			$date_updated = explode(' ', $row->updated)[0] == '0000-00-00' ? '' : explode(' ', $row->updated)[0];

			$output .= '<tr>';
			$output .= '     <td><a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.htmlentities($show_id).'</a></td>';
			if ($objParent->userHasWriteRights()) {
				if ($parentNS == 'oid') {
					if ($one_weid_available) {
						if ($obj->isWeid(false)) {
							$output .= '	<td>'.$obj->weidArc().'</td>';
						} else {
							$output .= '	<td>n/a</td>';
						}
					}
					$output .= '     <td><input type="text" id="asn1ids_'.$row->id.'" value="'.implode(', ', $asn1ids).'"></td>';
					$output .= '     <td><input type="text" id="iris_'.$row->id.'" value="'.implode(', ', $iris).'"></td>';
				}
				$output .= '     <td><input type="text" id="ra_email_'.$row->id.'" value="'.htmlentities($row->ra_email).'"></td>';
				$output .= '     <td><input type="text" id="comment_'.$row->id.'" value="'.htmlentities($row->comment).'"></td>';
				$output .= '     <td><input type="checkbox" id="hide_'.$row->id.'" '.($row->confidential ? 'checked' : '').'></td>';
				$output .= '     <td><button type="button" name="update_'.$row->id.'" id="update_'.$row->id.'" class="btn btn-success btn-xs update" onclick="crudActionUpdate('.js_escape($row->id).', '.js_escape($parent).')">Update</button></td>';
				$output .= '     <td><button type="button" name="delete_'.$row->id.'" id="delete_'.$row->id.'" class="btn btn-danger btn-xs delete" onclick="crudActionDelete('.js_escape($row->id).', '.js_escape($parent).')">Delete</button></td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			} else {
				if ($asn1ids == '') $asn1ids = '<i>(none)</i>';
				if ($iris == '') $iris = '<i>(none)</i>';
				if ($parentNS == 'oid') {
					if ($one_weid_available) {
						if ($obj->isWeid(false)) {
							$output .= '	<td>'.$obj->weidArc().'</td>';
						} else {
							$output .= '	<td>n/a</td>';
						}
					}
					$asn1ids_ext = array();
					foreach ($asn1ids as $asn1id) {
						$asn1ids_ext[] = '<a href="?goto='.urlencode($row->id).'" onclick="openAndSelectNode('.js_escape($row->id).', '.js_escape($parent).'); return false;">'.$asn1id.'</a>';
					}
					$output .= '     <td>'.implode(', ', $asn1ids_ext).'</td>';
					$output .= '     <td>'.implode(', ', $iris).'</td>';
				}
				$output .= '     <td><a '.OIDplus::gui()->link('oidplus:rainfo$'.str_replace('@','&',$row->ra_email)).'>'.htmlentities(empty($row->ra_name) ? str_replace('@','&',$row->ra_email) : $row->ra_name).'</a></td>';
				$output .= '     <td>'.htmlentities($row->comment).'</td>';
				$output .= '     <td>'.$date_created.'</td>';
				$output .= '     <td>'.$date_updated.'</td>';
			}
			$output .= '</tr>';
		}

		$result = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($parent));
		$parent_ra_email = $result->num_rows() > 0 ? $result->fetch_object()->ra_email : '';

		if ($objParent->userHasWriteRights()) {
			$output .= '<tr>';
			$prefix = is_null($objParent) ? '' : $objParent->crudInsertPrefix();
			if ($parentNS == 'oid') {
				if ($objParent->isWeid(true)) {
					$output .= '     <td>'.$prefix.' <input oninput="frdl_oidid_change()" type="text" id="id" value="" style="width:100%;min-width:100px"></td>'; // TODO: idee classname vergeben, z.B. "OID" und dann mit einem oid-spezifischen css die breite einstellbar machen, somit hat das plugin mehr kontrolle �ber das aussehen und die mindestbreiten
					$output .= '     <td><input type="text" name="weid" id="weid" value="" oninput="frdl_weid_change()"></td>';
				} else {
					$output .= '     <td>'.$prefix.' <input type="text" id="id" value="" style="width:100%;min-width:50px"></td>'; // TODO: idee classname vergeben, z.B. "OID" und dann mit einem oid-spezifischen css die breite einstellbar machen, somit hat das plugin mehr kontrolle �ber das aussehen und die mindestbreiten
					if ($one_weid_available) $output .= '     <td></td>'; // WEID-editor not available for root nodes. Do it manually, please
				}
			} else {
				$output .= '     <td>'.$prefix.' <input type="text" id="id" value=""></td>';
			}
			if ($parentNS == 'oid') $output .= '     <td><input type="text" id="asn1ids" value=""></td>';
			if ($parentNS == 'oid') $output .= '     <td><input type="text" id="iris" value=""></td>';
			$output .= '     <td><input type="text" id="ra_email" value="'.htmlentities($parent_ra_email).'"></td>';
			$output .= '     <td><input type="text" id="comment" value=""></td>';
			$output .= '     <td><input type="checkbox" id="hide"></td>';
			$output .= '     <td><button type="button" name="insert" id="insert" class="btn btn-success btn-xs update" onclick="crudActionInsert('.js_escape($parent).')">Insert</button></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '     <td></td>';
			$output .= '</tr>';
		} else {
			if ($items_total-$items_hidden == 0) {
				$cols = ($parentNS == 'oid') ? 7 : 5;
				if ($one_weid_available) $cols++;
				$output .= '<tr><td colspan="'.$cols.'">No items available</td></tr>';
			}
		}

		$output .= '</table>';
		$output .= '</div></div>';

		if ($items_hidden == 1) {
			$output .= '<p>'.$items_hidden.' item is hidden. Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to see it.</p>';
		} else if ($items_hidden > 1) {
			$output .= '<p>'.$items_hidden.' items are hidden. Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to see them.</p>';
		}

		return $output;
	}

	// 'quickbars' added 11 July 2019: Disabled because of two problems:
	//                                 1. When you load TinyMCE via AJAX using the left menu, the quickbar is immediately shown, even if TinyMCE does not have the focus
	//                                 2. When you load a page without TinyMCE using the left menu, the quickbar is still visible, although there is no edit
	// 'colorpicker', 'textcolor' and 'contextmenu' added in 07 April 2020, because it is built in in the core.
	public static $exclude_tinymce_plugins = array('fullpage', 'bbcode', 'quickbars', 'colorpicker', 'textcolor', 'contextmenu');

	protected static function showMCE($name, $content) {
		$mce_plugins = array();
		foreach (glob(__DIR__ . '/../../3p/tinymce/plugins/*') as $m) { // */
			$mce_plugins[] = basename($m);
		}

		foreach (self::$exclude_tinymce_plugins as $exclude) {
			$index = array_search($exclude, $mce_plugins);
			if ($index !== false) unset($mce_plugins[$index]);
		}

		$out = '<script>
				tinymce.remove("#'.$name.'");
				tinymce.EditorManager.baseURL = "3p/tinymce";
				tinymce.init({
					document_base_url: "'.OIDplus::getSystemUrl().'",
					selector: "#'.$name.'",
					height: 200,
					statusbar: false,
//					menubar:false,
//					toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | fontsizeselect",
					toolbar: "undo redo | styleselect | bold italic underline forecolor | bullist numlist | outdent indent | table | fontsizeselect",
					plugins: "'.implode(' ', $mce_plugins).'",
					mobile: {
						theme: "mobile",
						toolbar: "undo redo | styleselect | bold italic underline forecolor | bullist numlist | outdent indent | table | fontsizeselect",
						plugins: "'.implode(' ', $mce_plugins).'"
					}

				});
			</script>';

		$content = htmlentities($content); // For some reason, if we want to display the text "<xyz>" in TinyMCE, we need to double-encode things! &lt; will not be accepted, we need &amp;lt; ... why?

		$out .= '<textarea name="'.htmlentities($name).'" id="'.htmlentities($name).'">'.trim($content).'</textarea><br>';

		return $out;
	}

	public static function generateContentPage($id) {
		$out = array();

		$handled = false;
		$out['title'] = '';
		$out['icon'] = '';
		$out['text'] = '';

		// === Plugins ===

		foreach (OIDplus::getPagePlugins('*') as $plugin) {
			$plugin->gui($id, $out, $handled);
		}

		// === Everything else (objects) ===

		if (!$handled) {
			try {
				$obj = OIDplusObject::parse($id);
			} catch (Exception $e) {
				$out['title'] = 'Error';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = htmlentities($e->getMessage());
				return $out;
			}

			if ((!is_null($obj)) && (!$obj->userHasReadRights())) {
				$out['title'] = 'Access denied';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = '<p>Please <a '.OIDplus::gui()->link('oidplus:login').'>log in</a> to receive information about this object.</p>';
				return $out;
			}

			// ---

			$parent = null;
			$res = null;
			$row = null;
			$matches_any_registered_type = false;
			foreach (OIDplus::getEnabledObjectTypes() as $ot) {
				if ($obj = $ot::parse($id)) {
					$matches_any_registered_type = true;
					if ($obj->isRoot()) {
						$obj->getContentPage($out['title'], $out['text'], $out['icon']);
						$parent = null; // $obj->getParent();
						break;
					} else {
						$res = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($obj->nodeId()));
						if ($res->num_rows() == 0) {
							http_response_code(404);
							$out['title'] = 'Object not found';
							$out['icon'] = 'img/error_big.png';
							$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
							return $out;
						} else {
							$row = $res->fetch_array(); // will be used further down the code
							$obj->getContentPage($out['title'], $out['text'], $out['icon']);
							if (empty($out['title'])) $out['title'] = explode(':',$id,2)[1];
							$parent = $obj->getParent();
							break;
						}
					}
				}
			}
			if (!$matches_any_registered_type) {
				http_response_code(404);
				$out['title'] = 'Object not found';
				$out['icon'] = 'img/error_big.png';
				$out['text'] = 'The object <code>'.htmlentities($id).'</code> was not found in this database.';
				return $out;
			}

			// ---

			if ($parent) {
				if ($parent->isRoot()) {

					$parent_link_text = $parent->objectTypeTitle();
					$out['text'] = '<p><a '.OIDplus::gui()->link($parent->root()).'><img src="img/arrow_back.png" width="16"> Parent node: '.htmlentities($parent_link_text).'</a></p>' . $out['text'];

				} else {
					$res_ = OIDplus::db()->query("select * from ".OIDPLUS_TABLENAME_PREFIX."objects where id = ?", array($parent->nodeId()));
					if ($res_->num_rows() > 0) {
						$row_ = $res_->fetch_array();
	
						$parent_title = $row_['title'];
						if (empty($parent_title) && ($parent->ns() == 'oid')) {
							// If not title is available, then use an ASN.1 identifier
							$res_ = OIDplus::db()->query("select name from ".OIDPLUS_TABLENAME_PREFIX."asn1id where oid = ?", array($parent->nodeId()));
							if ($res_->num_rows() > 0) {
								$row_ = $res_->fetch_array();
								$parent_title = $row_['name']; // TODO: multiple ASN1 ids?
							}
						}
	
						$parent_link_text = empty($parent_title) ? explode(':',$parent->nodeId())[1] : $parent_title.' ('.explode(':',$parent->nodeId())[1].')';
	
						$out['text'] = '<p><a '.OIDplus::gui()->link($parent->nodeId()).'><img src="img/arrow_back.png" width="16"> Parent node: '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
					} else {
						$out['text'] = '';
					}
				}
			} else {
				$parent_link_text = 'Go back to front page';
				$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:system').'><img src="img/arrow_back.png" width="16"> '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
			}

			// ---

			if (!is_null($row) && isset($row['description'])) {
				if (empty($row['description'])) {
					if (empty($row['title'])) {
						$desc = '<p><i>No description for this object available</i></p>';
					} else {
						$desc = $row['title'];
					}
				} else {
					$desc = OIDplusGui::objDescription($row['description']);
				}

				if ($obj->userHasWriteRights()) {
					$rand = ++self::$crudCounter;
					$desc = '<noscript><p><b>You need to enable JavaScript to edit title or description of this object.</b></p>'.$desc.'</noscript>';
					$desc .= '<div class="container box" style="display:none" id="descbox_'.$rand.'">';
					$desc .= 'Title: <input type="text" name="title" id="titleedit" value="'.htmlentities($row['title']).'"><br><br>Description:<br>';
					$desc .= self::showMCE('description', $row['description']);
					$desc .= '<button type="button" name="update_desc" id="update_desc" class="btn btn-success btn-xs update" onclick="updateDesc()">Update description</button>';
					$desc .= '</div>';
					$desc .= '<script>document.getElementById("descbox_'.$rand.'").style.display = "block";</script>';
				}
			} else {
				$desc = '';
			}

			// ---

			if (strpos($out['text'], '%%DESC%%') !== false)
				$out['text'] = str_replace('%%DESC%%',    $desc,                              $out['text']);
			if (strpos($out['text'], '%%CRUD%%') !== false)
				$out['text'] = str_replace('%%CRUD%%',    self::showCrud($id),                $out['text']);
			if ((strpos($out['text'], '%%RA_INFO%%') !== false) && isset($row['ra_email']) && !empty($row['ra_email']))
				$out['text'] = str_replace('%%RA_INFO%%', OIDplusPagePublicRaInfo::showRaInfo($row['ra_email']), $out['text']);

			$alt_ids = $obj->getAltIds();
			if (count($alt_ids) > 0) {
				$out['text'] .= "<h2>Alternative Identifiers</h2>";
				foreach ($alt_ids as $alt_id) {
					$ns = $alt_id->getNamespace();
					$aid = $alt_id->getId();
					$aiddesc = $alt_id->getDescription();
					$out['text'] .= "$aiddesc <code>$ns:$aid</code><br>";
				}
			}

			foreach (OIDplus::getPagePlugins('public') as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
			foreach (OIDplus::getPagePlugins('ra')     as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
			foreach (OIDplus::getPagePlugins('admin')  as $plugin) $plugin->modifyContent($id, $out['title'], $out['icon'], $out['text']);
		} else {
			// Other pages (search, whois, etc.)
			/*
			if ($id != 'oidplus:system') {
				$parent_link_text = 'Go back to front page';
				$out['text'] = '<p><a '.OIDplus::gui()->link('oidplus:system').'><img src="img/arrow_back.png" width="16"> '.htmlentities($parent_link_text).'</a></p>' . $out['text'];
			}
			*/
		}

		return $out;
	}

	public static function link($goto) {
		if (strpos($goto, '#') !== false) {
			list($goto, $anchor) = explode('#', $goto, 2);
			return 'href="?goto='.urlencode($goto).'#'.htmlentities($anchor).'" onclick="openOidInPanel('.js_escape($goto).', true, '.js_escape($anchor).'); return false;"';
		} else {
			return 'href="?goto='.urlencode($goto).'" onclick="openOidInPanel('.js_escape($goto).', true); return false;"';
		}
	}
}
