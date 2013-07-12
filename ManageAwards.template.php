<?php

// Version: 2.0, ManageAwards
function template_main()
{
	global $context, $txt, $settings;

	// Check if there are any awards
	if (empty($context['categories']))
		echo '
			<div class="error">
				<span>', $txt['awards_error_no_badges'], '</span>
			</div>';
	else
	{
		foreach ($context['categories'] as $key => $category)
		{
			echo '
			<div class="cat_bar">
				<h3 class="catbg">
					', ($key != 1) ? '<a href="' . $category['delete'] . '" onclick="return confirm(\'' . $txt['awards_confirm_delete_category'] . '\');" title="' . $txt['awards_button_delete'] . '">
						<img src="' . $settings['images_url'] . '/awards/delete.png" alt="' . $txt['awards_button_delete'] . '" />
					</a> ' : '', '
					<a href="', $category['edit'], '" title="', $txt['awards_button_edit'], '">
						<img src="', $settings['images_url'], '/awards/modify.png" alt="', $txt['awards_button_edit'], '" />
					</a>
					<a href="', $category['view'], '">', $category['name'], '</a>
				</h3>
			</div>

			<table class="table_grid" width="100%">
			<thead>
				<tr class="titlebg">
					<th scope="col" class="first_th smalltext" width="15%">', $txt['awards_image'], '</th>
					<th scope="col" class="smalltext" width="15%">', $txt['awards_mini'], '</th>
					<th scope="col" class="smalltext" width="25%">', $txt['awards_name'], '</th>
					<th scope="col" class="smalltext" width="35%">', $txt['awards_description'], '</th>
					<th scope="col" class="last_th smalltext" width="10%">', $txt['awards_modify'], '</th>
				</tr>
			</thead>
			<tbody>';

			$which = false;

			foreach ($category['awards'] as $award)
			{
				$which = !$which;

				echo '
					<tr class="windowbg', $which ? '2' : '', '">
						<td align="center"><img src="', $award['img'], '" alt="', $award['award_name'], '" /></td>
						<td align="center"><img src="', $award['small'], '" alt="', $award['award_name'], '" /></td>
						<td>', $award['award_name'], '</td>
						<td>', $award['description'], '</td>
						<td class="smalltext">
							<a href="', $award['edit'], '" title="', $txt['awards_button_edit'], '"><img src="', $settings['images_url'], '/awards/modify.png" alt="" /></a>
							<a href="', $award['delete'], '" onclick="return confirm(\'', $txt['awards_confirm_delete_award'], '\');" title="', $txt['awards_button_delete'], '"><img src="', $settings['images_url'], '/awards/delete.png" alt="" /></a>
							<a href="', $award['assign'], '" title="', $txt['awards_button_assign'], '"><img src="', $settings['images_url'], '/awards/assign.png" alt="" /></a>
							<a href="', $award['view_assigned'], '" title="', $txt['awards_button_members'], '"><img src="', $settings['images_url'], '/awards/user.png" alt="" /></a>
						</td>
					</tr>
					<tr class="titlebg">
						<td colspan="5">&nbsp;</td>
					</tr>';
			}

			echo '
				</tbody>
				</table>';
		}

		// Show the pages
		echo '
			<span class="smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>';
	}
}

function template_modify()
{
	global $context, $txt, $scripturl;

	echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=modify" method="post" name="award" id="award" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
					<div class="cat_bar">
						<h3 class="catbg">
							', (isset($_GET['saved']) ? $txt['awards_saved_award'] : ($context['editing'] == true ? $txt['awards_edit_award'] . ' - ' . $context['award']['award_name'] : $txt['awards_add_award'])), '
						</h3>
					</div>';

	echo '
					<div class="windowbg2">
						<span class="topslice"><span></span></span>
						<div class="content">
							<dl class="settings">
								<dt>
									<label for="award_name">', $txt['awards_badge_name'], '</label>:
								</dt>
									<dd>
										<input type="text" name="award_name" id="award_name" value="', $context['award']['award_name'], '" size="30" />
									</dd>
								<dt>
									<label for="description">', $txt['awards_edit_description'], '</label>:
								</dt>
									<dd>
										<input type="text" name="description" id="description" value="', $context['award']['description'], '" size="30" />
									</dd>
								<dt>
									<label for="description">', $txt['awards_category'], '</label>:
								</dt>
									<dd>
										<select name="id_category" id="id_category">';

	foreach ($context['categories'] as $category)
		echo '
											<option value="', $category['id'], '"', ($category['id'] == $context['award']['category']) ? ' selected="selected"' : '', '>', $category['name'], '</option>';

	echo '
										</select>
									</dd>
								<dt>
									<label for="awardFile">', $txt['awards_badge_upload'], '</label>:
								</dt>
									<dd>
										<input type="file" name="awardFile" id="awardFile" size="40" />
									</dd>
								<dt>
									<label for="awardFileMini">', $txt['awards_badge_upload_mini'], '</label>:
								</dt>
									<dd>
										<input type="file" name="awardFileMini" id="awardFileMini" size="40" />
									</dd>
							</dl>
							<input type="hidden" name="id" value="', $context['award']['id'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<input type="submit" name="award_save" value="', $context['editing'] ? $txt['save'] : $txt['awards_submit'], '" accesskey="s" />
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</form>
				<br class="clear" />';
}

function template_assign()
{
	global $context, $scripturl, $txt, $modSettings, $settings;

	echo '
			<script language="JavaScript" type="text/javascript">
				function showaward(){
					document.getElementById(\'awards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + document.forms.assign.award.options[document.forms.assign.award.selectedIndex].id;
				}
			</script>
			<form action="', $scripturl, '?action=admin;area=awards;sa=assign;step=2" method="post" name="assign" id="assign" accept-charset="', $context['character_set'], '">
				<div class="cat_bar">
					<h3 class="catbg">
						', $txt['awards_select_badge'], '
					</h3>
				</div>
				<div class="windowbg">
					<span class="topslice"><span></span></span>
					<div class="content">
						<dl class="settings">
							<dt>
								<select name="award" onchange="showaward();" size="10">';

	// Loop and show the drop down.
	foreach ($context['awards'] as $key => $award)
		echo '
									<option value="', $key, '" id="', $award['filename'], '" ', isset($_REQUEST['id']) && $_REQUEST['id'] == $key ? 'selected="selected"' : '', '>', $award['award_name'], '</option>';

	echo '
								</select>
							</dt>
								<dd>
									<img id="awards" src="', isset($_REQUEST['award']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['award']]['filename'] : '', '" align="middle" alt="" />
								</dd>
							<dt>
								<label for="date_received"><b>', $txt['awards_date'], '</b></label>:
							</dt>
								<dd id="date_received">';

	// The month... and day... and year...
	echo '
									<select name="month" tabindex="', $context['tabindex']++, '">';
	foreach ($txt['months'] as $key => $month)
		echo '
										<option value="', $key, '" ', date('F') == $month ? 'selected="selected"' : '', '>', $month, '</option>';

	echo '
									</select>
									<select name="day" tabindex="', $context['tabindex']++, '">';
	for ($i = 1; $i <= 31; $i++)
		echo '
										<option value="', $i, '" ', date('j') == $i ? 'selected="selected"' : '', '>', $i, '</option>';

	echo '
									</select>
									<select name="year" tabindex="', $context['tabindex']++, '">';
	for ($i = date('Y') + 5; $i >= date('Y') - 5; $i--)
		echo '
										<option value="', $i, '" ', date('Y') == $i ? 'selected="selected"' : '', '>', $i, '</option>';

	echo '
									</select>
								</dd>
							<dt>
								<label for="to_control"><b>', $txt['awards_select_member'], ':</b></label>
							</dt>
								<dd>
									<input class="smalltext" type="text" name="to" id="to_control" tabindex="', $context['tabindex']++, '" size="40" style="width: 130px;" />
									<div id="to_item_list_container"></div>
								</dd>
						</dl>
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<input type="submit" value="', $txt['save'], '" tabindex="', $context['tabindex']++, '" />
					</div>
					<span class="botslice"><span></span></span>
				</div>
			</form>
			<br class="clear" />';

	echo '
		<script language="JavaScript" type="text/javascript" src="', $settings['default_theme_url'], '/scripts/awards.js?rc1"></script>
		<script language="JavaScript" type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?rc1"></script>
		<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
			var oAwardSend = new smf_AwardSend({
				sSelf: \'oAwardSend\',
				sSessionId: \'', $context['session_id'], '\',
				sSessionVar: \'', $context['session_var'], '\',
				sTextDeleteItem: \'', $txt['autosuggest_delete_item'], '\',
				sToControlId: \'to_control\',
				aToRecipients: [
				]
			});

			function saveEntities()
			{
				var textFields = ["subject", "message"];
				for (i in textFields)
					if (document.forms.postmodify.elements[textFields[i]])
						document.forms.postmodify[textFields[i]].value = document.forms.postmodify[textFields[i]].value.replace(/&#/g, "&#38;#");
			}
		// ]]></script>';
}

function template_view_assigned()
{
	global $context, $scripturl, $txt;

	echo '
				<form action=', $scripturl, '?action=admin;area=awards;sa=viewassigned;id=', $context['award']['id'], '" method="post" name="unassign" id="unassign" accept-charset="', $context['character_set'], '">
					<div class="cat_bar">
						<h3 class="catbg">
							', $txt['awards_view_assigned'], '
						</h3>
					</div>
					<p class="description align_center">
						<img src="', $context['award']['img'], '" alt="', $context['award']['name'], '" /> -
						<strong>', $context['award']['name'], '</strong> -
						', $context['award']['description'], '
					</p>
					<table class="table_grid" width="100%">
					<thead>
						<tr class="catbg">
							<th scope="col" class"first_th smalltext" ', empty($context['award']['members']) ? 'colspan="2"' : '', '>', $txt['members'], '</th>';

	// Show the "check all" checkbox if there are members
	if (!empty($context['award']['members']))
		echo '
							<td>
								<input type="checkbox" id="checkAllGroups" onclick="invertAll(this, this.form, \'member\');" class="check" />
							</td>';

	echo '
						</tr>
					</thead>
					<tbody>';

	// Check if there are assigned members
	if (empty($context['award']['members']))
		echo '
						<tr class="windowbg2">
							<td>', sprintf($txt['awards_no_assigned_members'], $scripturl . '?action=admin;area=awards;sa=assign;id=' . $context['award']['id'] . ';step=1'), '</td>
						</tr>';
	else
	{
		$which = false;

		foreach ($context['award']['members'] as $member)
		{
			$which = !$which;

			echo '
						<tr class="windowbg', $which ? '2' : '', '">
							<td><label for="member[', $member['id'], ']">', $member['link'], '</label></td>
							<td width="3%" align="center"><input type="checkbox" name="member[', $member['id'], ']" id="member[', $member['id'], ']" value="', $member['id'], '" /></td>
						</tr>';
		}
	}

	// Unassign
	if (!empty($context['award']['members']))
		echo '
						<tr class="windowbg">
							<td align="right" colspan="2">
								<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
								<input type="hidden" name="id" value="', $context['award']['id'], '" />
								<input type="submit" name="unassign" value="', $txt['awards_unassign'], '" accesskey="s" />
							</td>
						</tr>';

	echo '
					</tbody>
					</table>
				</form>';
}

function template_settings()
{
	global $context, $txt, $scripturl, $modSettings;

	echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=settings;saved=1" method="post" name="badge" id="badge" accept-charset="', $context['character_set'], '" enctype="multipart/form-data" style="padding:0; margin: 0;">
					<div class="cat_bar">
						<h3 class="catbg">
							', (isset($_GET['saved']) ? $txt['awards_saved_settings'] : $txt['awards_settings']), '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">
							<dl class="settings">
								<dt>
									<strong><label for="awards_dir">', $txt['awards_badges_dir'], '</label>:</strong><br />
									<span class="smalltext">', $txt['awards_badges_dir_desc'], '</span>
								</dt>
									<dd>
										<input type="text" name="awards_dir" id="awards_dir" value="', empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'], '" size="30" />
									</dd>
								<dt>
									<strong><label for="awards_favorites">', $txt['awards_favorite'], '</label>:</strong><br />
									<span class="smalltext">', $txt['awards_favorite_desc'], '</span>
								</dt>
									<dd>
										<input type="checkbox" name="awards_favorites" id="awards_favorites" ', empty($modSettings['awards_favorites']) ? '' : 'checked="checked"', ' />
									</dd>
								<dt>
									<strong><label for="awards_in_post">', $txt['awards_in_post'], '</label>:</strong><br />
									<span class="smalltext">', $txt['awards_in_post_desc'], '</span>
								</dt>
									<dd>
										<input type="text" name="awards_in_post" id="awards_in_post" value="', empty($modSettings['awards_in_post']) ? '0' : $modSettings['awards_in_post'], '" size="3" />
									</dd>
							</dl>
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<input type="submit" name="save_settings" value="', $txt['save'], '" accesskey="s" />
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</form>';
}

function template_edit_category()
{
	global $context, $txt, $scripturl;

	echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=editcategory" method="post" name="category" id="category" accept-charset="', $context['character_set'], '" style="padding:0; margin: 0;">
					<div class="cat_bar">
						<h3 class="catbg">
							', (isset($_GET['saved']) && $_GET['saved'] == '1') ? $txt['awards_saved_category'] : $context['editing'] == true ? $txt['awards_edit_category'] : $txt['awards_add_category'], '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">
							<dl class="settings">
								<dt>
									<label for="category_name">', $txt['awards_category_name'], '</label>:
								</dt>
									<dd>
										<input type="text" name="category_name" id="category_name" value="', $context['category']['name'], '" size="30" />
									</dd>
							</dl>
							<input type="hidden" name="id_category" value="', $context['category']['id'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<input type="submit" name="category_save" value="', $context['editing'] ? $txt['save'] : $txt['awards_submit'], '" accesskey="s" />
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</form>';
}

function template_list_categories()
{
	global $context, $txt, $settings, $scripturl;

	echo '
				<div class="cat_bar">
					<h3 class="catbg">
						', $txt['awards_list_categories'], '
					</h3>
				</div>
				<table class="table_grid" width="100%">
				<thead>
					<tr class="catbg">
						<th scope="col" class="first_th smalltext">&nbsp;</th>
						<th scope="col" class="smalltext"><b>', $txt['awards_category_name'], '</b></th>
						<th scope="col" class="last_th smalltext"><b>', $txt['awards_num_in_category'], '</b></th>
					</tr>
				</thead>
				<tbody>';

	// Check if there are any awards
	if (empty($context['categories']))
		echo '
					<tr class="windowbg2">
						<td colspan="3">', $txt['awards_error_no_categories'], '</td>
					</tr>';
	else
	{
		$which = false;

		foreach ($context['categories'] as $cat)
		{
			$which = !$which;

			echo '
					<tr class="windowbg', $which ? '2' : '', '">
						<td width="10%" align="center">
							<a href="', $cat['edit'], '" title="', $txt['awards_button_edit'], '"><img src="', $settings['images_url'], '/awards/modify.png" alt="" /></a> ', ($cat['id'] != 1) ? '
							<a href="' . $cat['delete'] . '" onclick="return confirm(\'' . $txt['awards_confirm_delete_category'] . '\');" title="' . $txt['awards_button_delete'] . '">
								<img src="' . $settings['images_url'] . '/awards/delete.png" alt="" />
							</a>' : '', '
						</td>
						<td width="60%" align="left">
							', $cat['name'], '
						</td>
						<td width="30%" align="center">
							', empty($cat['awards']) ? '0' : '<a href="' . $scripturl . '?action=admin;area=awards;sa=viewcategory;id=' . $cat['id'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '">' . $cat['awards'] . '</a>', '
						</td>
					</tr>';
		}

		echo '
					<tr class="catbg">
						<td align="right" colspan="3">
							<a href="', $scripturl, '?action=admin;area=awards;sa=editcategory" title="', $txt['awards_add_category'], '">
								<img src="', $settings['images_url'], '/awards/add.png" alt="" />
							</a>
							<!-- </c></d></e></f></g> ...will it ever end? -->
						</td>
					</tr>';
	}

	echo '
				</tbody>
				</table>';
}

function template_view_category()
{
	global $context, $txt;

	echo '
				<div class="cat_bar">
					<h3 class="catbg">
						', $context['category'], '
					</h3>
				</div>
				<table class="table_grid" width="100%">
				<thead>
					<tr class="catbg">
						<th scope="col" class="first_th smalltext">', $txt['awards_image'], '</th>
						<th scope="col" class="smalltext">', $txt['awards_mini'], '</th>
						<th scope="col" class="smalltext">', $txt['awards_name'], '</th>
						<th scope="col" class="last_th smalltext">', $txt['awards_description'], '</th>
					</tr>
				</thead>
				<tbody>';

	// Check if there are any awards
	if (empty($context['awards']))
		echo '
					<tr class="windowbg2">
						<td colspan="4">', $txt['awards_error_empty_category'], '</td>
					</tr>';
	else
	{
		$which = false;

		foreach ($context['awards'] as $award)
		{
			$which = !$which;

			echo '
					<tr class="windowbg', $which ? '2' : '', '">
						<td align="center">
							<img src="', $award['img'], '" />
						</td>
						<td align="center">
							<img src="', $award['small'], '" />
						</td>
						<td>
							<a href="', $award['edit'], '">', $award['name'], '</a>
						</td>
						<td>
							', $award['description'], '
						</td>
					</tr>';
		}

		// Show the pages
		echo '
					<tr class="catbg">
						<td align="left" colspan="4">
							<span class="smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>
						</td>
					</tr>';
	}

	echo '
				</tbody>
				</table>';
}