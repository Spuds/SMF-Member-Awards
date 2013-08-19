<?php

// Version: 3.0 AwardsManage.template

function template_main()
{
	global $context;

	for ($i = 0; $i < $context['count']; $i++)
	{
		template_show_list('awards_cat_list_' . $i);
		echo '<br /><br />';
	}
}

function template_modify()
{
	global $context, $txt, $scripturl, $settings;

	echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=modify" method="post" name="award" id="award" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">';

	if (isset($_GET['saved']))
		echo'
					<span class="upperframe"><span></span></span>
					<div class="roundframe">
						<div id="savestatus"><img class="icon" src="' . $settings['images_url'] . '/awards/award_save.png" alt="" />&nbsp;',
							$txt['awards_saved_award'], '
						</div>
					</div>
					<span class="lowerframe"><span></span></span>';

	echo '
					<div class="cat_bar">
						<h3 class="catbg">
							', ($context['editing'] == true ? $txt['awards_edit_award'] . ' - ' . $context['award']['award_name'] : ('<img class="icon" src="' . $settings['images_url'] . '/awards/award_add.png" alt="" />&nbsp;' . $txt['awards_add_award'])), '
						</h3>
					</div>';

	echo '
					<div class="windowbg2">
						<span class="topslice"><span></span></span>
						<div class="content">
							<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
								<legend>', $txt['awards_add_name'], '</legend>
								<dl class="settings">
									<dt>
										<label for="award_name">', $txt['awards_badge_name'], '</label>
									</dt>
									<dd>
										<input type="text" name="award_name" id="award_name" value="', $context['award']['award_name'], '" size="30" />
									</dd>

									<dt>
										<label for="description">', $txt['awards_edit_description'], '</label>
									</dt>
									<dd>
										<input type="text" name="description" id="description" value="', $context['award']['description'], '" size="30" />
									</dd>

									<dt>
										<label for="id_category">', $txt['awards_category'], '</label>:
									</dt>
									<dd>
										<select name="id_category" id="id_category">';

	foreach ($context['categories'] as $category)
		echo '
											<option value="', $category['id'], '"', ($category['id'] == $context['award']['category']) ? ' selected="selected"' : '', '>', $category['name'], '</option>';

	echo '
										</select>
									</dd>
								</dl>
							</fieldset>

							<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
								<legend>', $txt['awards_add_type'], '</legend>
								<dl class="settings">
									<dt>
										<label for="id_type">', $txt['awards_type'], '</label>:
									</dt>
									<dd>
										<select name="id_type" id="id_type">';

	// our awards type list selection
	foreach ($context['award_types'] as $type)
		echo '
											<option value="', $type['id'], '"', (isset($context['award']['type']) && $type['id'] == $context['award']['type']) ? ' selected="selected"' : '', '>', $type['name'], '</option>';

	echo '
										</select>
									</dd>

									<dt>
										<label for="awardTrigger">', $txt['awards_trigger'], '</label>:
										<br />
										<span id="awardTrigger_desc" class="smalltext" ></span>';

	// and the descriptions for them, hidden and used by javascript to fill in the awardTrigger_desc span
	foreach ($context['award_types'] as $desc)
		echo '
										<span id="trigger_desc_', $desc['id'], '" style="display:none">', $desc['desc'], '</span>';

	echo '
									</dt>
										<dd>
											<input type="text" name="awardTrigger" id="awardTrigger" value="', $context['award']['trigger'], '" size="30" class="input_text"/>
										</dd>
								</dl>
							</fieldset>

							<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
								<legend>', $txt['awards_add_image'], '</legend>
								<dl class="settings">
									<dt>
										&nbsp;
									</dt>
									<dd>',
										!empty($context['award']['img']) ? '<img id="awardsfull" src="' . $context['award']['img'] . '" align="middle" alt="" />' : '&nbsp;', '
									</dd>
									<dt>
										<label for="awardFile">', $txt['awards_badge_upload'], '</label>:
									</dt>
									<dd>
										<input type="file" name="awardFile" id="awardFile" size="40" />
									</dd>
								</dl>';

	if (!empty($context['award']['img']))
		echo '
								<br class="clear" />';

	echo '
								<dl class="settings">
									<dt>
										&nbsp;
									</dt>
									<dd>',
										!empty($context['award']['small']) ? '<img id="awardssmall" src="' . $context['award']['small'] . '" align="middle" alt="" />' : '&nbsp;', '
									</dd>
									<dt>
										<label for="awardFileMini">', $txt['awards_badge_upload_mini'], '</label>
									</dt>
									<dd>
										<input type="file" name="awardFileMini" id="awardFileMini" size="40" />
									</dd>
								</dl>
								<dl class="settings">
									<dt>
										<label for="award_location">', $txt['awards_image_placement'], '</label>:
									</dt>
									<dd>
										<select name="award_location" id="award_location">';

	// our awards type list selection
	foreach ($context['award_placements'] as $type)
		echo '
											<option value="', $type['id'], '"', (isset($context['award']['location']) && $type['id'] == $context['award']['location']) ? ' selected="selected"' : '', '>', $type['name'], '</option>';

	echo '
										</select>
									</dd>
								</dl>
							</fieldset>

							<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
								<legend>', $txt['awards_extras'], '</legend>
								<dl class="settings">
									<dt>
										<label for="award_requestable">', $txt['awards_requestable'], '</label>:<br />
										<span class="smalltext">', $txt['awards_requestable_desc'], '</span>
									</dt>
									<dd>
										<input type="checkbox" name="award_requestable" id="award_requestable" ', empty($context['award']['requestable']) ? '' : 'checked="checked"', ' />
									</dd>
									<dt>
										<label for="award_assignable">', $txt['awards_assignable'], '</label>:<br />
										<span class="smalltext">', $txt['awards_assignable_desc'], '</span>
									</dt>
									<dd>
										<input type="checkbox" name="award_assignable" id="award_assignable" ', empty($context['award']['assignable']) ? '' : 'checked="checked"', ' />
									</dd>
								</dl>
							</fieldset>

							<input type="hidden" name="a_id" value="', $context['award']['id'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							<hr class="hrcolor" />
							<div class="righttext">
								<input type="submit" class="button_submit" name="award_save" value="', $context['editing'] ? $txt['save'] : $txt['awards_submit'], '" accesskey="s" />
							</div>
						</div>
						<span class="botslice"><span></span></span>
					</div>
					<br class="clear" />
				</form>';

	if (!empty($context['settings_post_javascript']))
		echo '
			<script type="text/javascript"><!-- // --><![CDATA[
			', $context['settings_post_javascript'], '
			// ]]></script>';
}

function template_assign_group()
{
	global $context, $scripturl, $txt, $modSettings, $settings;

	echo '
		<div id="awardpage">
			<span class="upperframe"><span></span></span>
			<div class="roundframe">
				<div id="welcome">',
					$txt['awards_assigngroup_intro'], '
				</div>
			</div>
			<span class="lowerframe"><span></span></span>

			<br class="clear" />

			<div id="awardassign">
				<form action="', $scripturl, '?action=admin;area=awards;sa=assigngroup;step=2" method="post" name="assigngroup" id="assigngroup" accept-charset="', $context['character_set'], '">
					<div class="floatleft" style="width:22%">
						<div class="cat_bar">
							<h3 class="catbg">
								<span class="ie6_header floatleft">', $txt['awards_select_badge'], '</span>
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
											<option title="', $award['description'], '" value="', $key, '" ', isset($_REQUEST['a_id']) && $_REQUEST['a_id'] == $key ? 'selected="selected"' : '', '>', $award['award_name'], '</option>';

	echo '
										</select>
									</dt>
								</dl>
							</div>
							<span class="botslice"><span></span></span>
						</div>
					</div>

					<div class="floatright" style="width:75%">
						<div class="cat_bar">
							<h3 class="catbg">
								<img class="icon" src="' . $settings['images_url'] . '/awards/award_add.png" alt="" />&nbsp;', $txt['awards_assign_badge'], '
							</h3>
						</div>
						<div class="windowbg">
							<span class="topslice"><span></span></span>
								<div class="content">
									<dl class="settings">
										<dt>
											<label for="awards"><b>', $txt['awards_image'], ':</b></label>
										</dt>
										<dd>
											<img id="awards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['filename'] : '', '" align="middle"  alt=""/>
										</dd>
										<dt>
											<label for="miniawards"><b>', $txt['awards_mini'], ':</b></label>
										</dt>
										<dd>
											<img id="miniawards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['minifile'] : '', '" align="middle"  alt=""/>
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
												<option value="', $key, '" ', date('F') == $month ? 'selected="selected"' : '', '>', $month, ' </option>';

	echo '
											</select>
											<select name="day" tabindex="', $context['tabindex']++, '">';

	for ($i = 1; $i <= 31; $i++)
		echo '
												<option value="', $i, '" ', date('j') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
											</select>
											<select name="year" tabindex="', $context['tabindex']++, '">';

	for ($i = date('Y') + 5; $i >= date('Y') - 5; $i--)
		echo '
												<option value="', $i, '" ', date('Y') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
											</select>
										</dd>
									</dl>
									<div class="title_bar">
										<h3 class="titlebg">
											<span class="ie6_header floatleft"><img class="icon" src="' . $settings['images_url'] . '/awards/group.png" alt="" />', $txt['awards_mem_group'], '</span>
										</h3>
									</div>
									<div class="windowbg">
										<dl class="settings">
											<dt>';

	foreach ($context['groups'] as $group)
		echo '
											<input type="checkbox" name="who[', $group['id'], ']" id="who', $group['id'], '" value="', $group['id'], '" class="input_check" /> ', $group['name'], ' <em>(', $group['member_count'], ')</em><br />';

	echo '
											<br class="clear" />
											<input type="checkbox" id="checkAllGroups" onclick="invertAll(this, this.form, \'who\');" class="input_check" /> <em>', $txt['check_all'], '</em>
										</dt>
										<dd>' . $txt['awards_mem_group_desc'] . '
										</dd>

									</dl>
									<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
									<hr class="hrcolor" />
									<div class="righttext">
										<input type="submit" class="button_submit" value="', $txt['awards_button_assign'], '" tabindex="', $context['tabindex']++, '" />
									</div></div>
								</div>
							<span class="botslice"><span></span></span>
						</div>
					</div>
				</form>
			</div>
		</div>
		<br class="clear" />';

	// Now create a javascript array from our php awards array so we can use it
	$script = "var awardValues = [";
	foreach ($context['awards'] as $key => $value)
	{
		if ($key < (count($context['awards']) - 1))
			$script = $script . implode(",", $value) . ',';
		else
			$script = $script . implode(",", $value) . "];\n";
	}
	$script = $script . "</script>";

	echo '
		<script type="text/javascript"><!-- // --><![CDATA[
			function showaward()
			{' . $context['awardsjavasciptarray'] . '
				document.getElementById(\'awards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assigngroup.award.value][\'filename\'];
				document.getElementById(\'miniawards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assigngroup.award.value][\'minifile\'];
			}
		// ]]></script>';
}

function template_assign()
{
	global $context, $scripturl, $txt, $modSettings, $settings;

	echo '
		<div id="awardpage">
			<span class="upperframe"><span></span></span>
			<div class="roundframe">
				<div id="welcome">',
					$txt['awards_assign_intro'], '
				</div>
			</div>
			<span class="lowerframe"><span></span></span>

			<br class="clear" />

			<div id="awardassign">
				<form action="', $scripturl, '?action=admin;area=awards;sa=assign;step=2" method="post" name="assign" id="assign" accept-charset="', $context['character_set'], '">
					<div class="floatleft" style="width:22%">
						<div class="cat_bar">
							<h3 class="catbg">
								<span class="ie6_header floatleft">', $txt['awards_select_badge'], '</span>
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
											<option title="', $award['description'], '" value="', $key, '" ', isset($_REQUEST['a_id']) && $_REQUEST['a_id'] == $key ? 'selected="selected"' : '', '>', $award['award_name'], '</option>';

	echo '
										</select>
									</dt>
								</dl>
							</div>
							<span class="botslice"><span></span></span>
						</div>
					</div>

					<div class="floatright" style="width:75%">
						<div class="cat_bar">
							<h3 class="catbg">
								<img class="icon" src="' . $settings['images_url'] . '/awards/award_add.png" alt="" />&nbsp;', $txt['awards_assign_badge'], '
							</h3>
						</div>
						<div class="windowbg">
							<span class="topslice"><span></span></span>
								<div class="content">
									<dl class="settings">
										<dt>
											<label for="awards"><b>', $txt['awards_image'], ':</b></label>
										</dt>
										<dd>
											<img id="awards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['filename'] : '', '" align="middle"  alt=""/>
										</dd>
										<dt>
											<label for="miniawards"><b>', $txt['awards_mini'], ':</b></label>
										</dt>
										<dd>
											<img id="miniawards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['minifile'] : '', '" align="middle"  alt=""/>
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
												<option value="', $key, '" ', date('F') == $month ? 'selected="selected"' : '', '>', $month, ' </option>';

	echo '
											</select>
											<select name="day" tabindex="', $context['tabindex']++, '">';
	for ($i = 1; $i <= 31; $i++)
		echo '
												<option value="', $i, '" ', date('j') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
											</select>
											<select name="year" tabindex="', $context['tabindex']++, '">';

	for ($i = date('Y') + 5; $i >= date('Y') - 5; $i--)
		echo '
												<option value="', $i, '" ', date('Y') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
											</select>
										</dd>
									</dl>
									<div class="title_bar">
										<h3 class="titlebg">
											<span class="ie6_header floatleft"><img class="icon" src="' . $settings['images_url'] . '/awards/user.png" alt="" />', $txt['awards_select_member'], '</span>
										</h3>
									</div>
									<div class="windowbg">
										<dl class="settings">
											<dt>
												<label for="to_control"><b>', $txt['awards_member_name'], ':</b></label><br />
												<input class="smalltext" type="text" name="to" id="to_control" tabindex="', $context['tabindex']++, '" size="40" style="width: 130px;" />
											</dt>
											<dd>
												<label for="to_control"><b>', $txt['awards_member_selected'], ':</b></label><br />
												<div id="to_item_list_container"></div>
											</dd>
										</dl>
									</div>
									<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
									<hr class="hrcolor" />
									<div class="righttext">
										<input type="submit" class="button_submit" value="', $txt['awards_button_assign'], '" tabindex="', $context['tabindex']++, '" />
									</div>
								</div>
							<span class="botslice"><span></span></span>
						</div>
					</div>
				</form>
			</div>
		</div>
		<br class="clear" />';

	// Now create a javascript array from our php awards array so we can use it
	$script = "var awardValues = [";
	foreach ($context['awards'] as $key => $value)
	{
		if ($key < (count($context['awards']) - 1))
			$script = $script . implode(",", $value) . ',';
		else
			$script = $script . implode(",", $value) . "];\n";
	}
	$script = $script . "</script>";

	echo '
		<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/awards.js?rc1"></script>
		<script type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?rc1"></script>
		<script type="text/javascript"><!-- // --><![CDATA[
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
			function showaward()
			{' . $context['awardsjavasciptarray'] . '
				document.getElementById(\'awards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assign.award.value][\'filename\'];
				document.getElementById(\'miniawards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assign.award.value][\'minifile\'];
			}
		// ]]></script>';
}

function template_assign_mass()
{
	global $context, $scripturl, $txt, $modSettings, $settings;

	echo '
		<div id="awardpage">
			<span class="upperframe"><span></span></span>
			<div class="roundframe">
				<div id="welcome">',
					$txt['awards_assignmass_intro'], '
				</div>
			</div>
			<span class="lowerframe"><span></span></span>

			<br class="clear" />

			<div id="awardassign">
				<div class="cat_bar">
					<h3 class="catbg">
						', $txt['awards_mem_group'], '
					</h3>
				</div>

				<div class="windowbg">
					<span class="topslice"><span></span></span>
					<div class="content">
						<form action="', $scripturl, '?action=admin;area=awards;sa=assignmass;step=2" method="post" name="assigngroup" id="assigngroup" accept-charset="', $context['character_set'], '">
							<dl class="select">
								<dt>';

	// create the membergroup selection list
	foreach ($context['groups'] as $group)
		echo '
									<input type="checkbox" name="who[', $group['id'], ']" id="who', $group['id'], '" value="', $group['id'], '" class="input_check"' . ((isset($_POST['who'][$group['id']])) ? 'checked="checked"' : '') . ' /> ', $group['name'], ' <em>(', $group['member_count'], ')</em>';

	echo '
									<br class="clear" />
								</dt>
							</dl>
							<div class="righttext">
								<input type="submit" class="button_submit" value="', $txt['awards_mem_group'], '" tabindex="', $context['tabindex']++, '" />
								<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
							</div>
						</form>
					</div>
					<span class="botslice"><span></span></span>
				</div>

				<br class="clear" />
				<form action="', $scripturl, '?action=admin;area=awards;sa=assignmass;step=3" method="post" name="assigngroup2" id="assigngroup2" accept-charset="', $context['character_set'], '">

				<div class="floatleft" style="width:22%">
					<div class="cat_bar">
						<h3 class="catbg">
							<span class="ie6_header floatleft">', $txt['awards_select_badge'], '</span>
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
										<option title="', $award['description'], '" value="', $key, '" ', isset($_REQUEST['a_id']) && $_REQUEST['a_id'] == $key ? 'selected="selected"' : '', '>', $award['award_name'], '</option>';

	echo '
									</select>
								</dt>
							</dl>
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>

				<div class="floatright" style="width:75%">

					<div class="cat_bar">
						<h3 class="catbg">
							<img class="icon" src="' . $settings['images_url'] . '/awards/award_add.png" alt="" />&nbsp;', $txt['awards_assign_badge'], '
						</h3>
					</div>

					<div class="windowbg">
						<span class="topslice"><span></span></span>
							<div class="content">
								<dl class="settings">
									<dt>
										<label for="awards"><b>', $txt['awards_image'], ':</b></label>
									</dt>
									<dd>
										<img id="awards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['filename'] : '', '" align="middle"  alt=""/>
									</dd>
									<dt>
										<label for="miniawards"><b>', $txt['awards_mini'], ':</b></label>
									</dt>
									<dd>
										<img id="miniawards" src="', isset($_REQUEST['a_id']) ? dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/' . $context['awards'][$_REQUEST['a_id']]['minifile'] : '', '" align="middle"  alt=""/>
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
											<option value="', $key, '" ', date('F') == $month ? 'selected="selected"' : '', '>', $month, ' </option>';

	echo '
										</select>
										<select name="day" tabindex="', $context['tabindex']++, '">';

	for ($i = 1; $i <= 31; $i++)
		echo '
											<option value="', $i, '" ', date('j') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
										</select>
										<select name="year" tabindex="', $context['tabindex']++, '">';

	for ($i = date('Y') + 5; $i >= date('Y') - 5; $i--)
		echo '
											<option value="', $i, '" ', date('Y') == $i ? 'selected="selected"' : '', '>', $i, ' </option>';

	echo '
										</select>
									</dd>
								</dl>

								<div class="title_bar">
									<h3 class="titlebg">
										<span class="ie6_header floatleft"><img class="icon" src="' . $settings['images_url'] . '/awards/multiple.png" alt="" />', $txt['awards_select_member'], '</span>
									</h3>
								</div>

								<div class="windowbg">';

	// show the member selection boxes if they have chosen a member group.
	if (empty($context['members']))
	{
		echo '
									<span class="upperframe"><span></span></span>
									<div class="roundframe">', $txt['awards_mem_mass_desc'], '</div>
									<span class="lowerframe"><span></span></span>';
	}
	else
	{
		// Select the members to give a badge
		$columns = 5;
		$counter = 0;

		echo '
									<table width="100%" cellpadding="5" cellspacing="0" border="0" align="center" class="tborder">';

		foreach ($context['members'] as $key => $member)
		{
			// Open the tr
			if ($counter == 0)
				echo '
										<tr>';

			// The member
			echo '
											<td class="windowbg2"><label for="member', $key, '"><input type="checkbox" name="member[]" id="member', $key, '" value="', $key, '" checked="checked" class="check" /> ', $member, '</label></td>';
			$counter++;

			// Close the tr
			if ($counter == $columns)
			{
				echo '
										</tr>';
				// Reset the counter
				$counter = 0;
			}
		}

		// Make sure the last one is closed
		if ($counter != 0)
		{
			if ($columns - $counter > 0)
			{
				for ($i = 0; $i < $columns - $counter; $i++)
					echo '
											<td class="windowbg2">&nbsp;</td>';
			}

			echo '
										</tr>';
		}

		// Close the table
		echo '
										<tr>
											<td  colspan="', $columns, '" align="right" class="windowbg2">
												<label for="checkAllGroups"><input type="checkbox" id="checkAllGroups" checked="checked" onclick="invertAll(this, this.form, \'member\');" class="check" /> <i>', $txt['check_all'], '</i></label><br />
											</td>
										</tr>
									</table>

									<br class="clear" />';

		// show the submit box
		echo '
									<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
									<hr class="hrcolor" />
									<div class="righttext">
										<input type="submit" class="button_submit" value="', $txt['awards_button_assign'], '" tabindex="', $context['tabindex']++, '" />
									</div>';
	}

	// close this page up
	echo '
								</div>
							</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>
				</form>
			</div>
		</div>
		<br class="clear" />';

	// Create a javascript array from our php awards array so we can use it
	$script = "var awardValues = [";
	foreach ($context['awards'] as $key => $value)
	{
		if ($key < (count($context['awards']) - 1))
			$script = $script . implode(",", $value) . ',';
		else
			$script = $script . implode(",", $value) . "];\n";
	}
	$script = $script . "</script>";

	echo '
		<script type="text/javascript"><!-- // --><![CDATA[
			function showaward()
			{' . $context['awardsjavasciptarray'] . '
				document.getElementById(\'awards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assigngroup2.award.value][\'filename\'];
				document.getElementById(\'miniawards\').src = \'' . dirname($scripturl) . '/' . $modSettings['awards_dir'] . '/\' + awards[document.forms.assigngroup2.award.value][\'minifile\'];
			}
		// ]]></script>';
}

/**
 * View all members that have been assigned an award (admin panel view)
 */
function template_view_assigned()
{
	global $context;

	echo '
	<span class="upperframe"><span></span></span>
	<div class="roundframe">
		<div id="award">
			<img style="vertical-align:middle;padding:0 5px" src="', $context['award']['img'], '" alt="', $context['award']['award_name'], '" />
			<img style="vertical-align:middle;padding:0 5px" src="', $context['award']['small'], '" alt="', $context['award']['award_name'], '" />
			- <strong>', $context['award']['award_name'], '</strong> - ', $context['award']['description'], '
		</div>
	</div>
	<span class="lowerframe"><span></span></span>
	<br class="clear" />';

	template_show_list('view_assigned');

	echo '
	<br class="clear" />';
}

/**
 * Template for showing our settings to control the modification
 */
function template_settings()
{
	global $context, $txt, $scripturl, $modSettings, $settings;

	if (isset($_GET['saved']))
		echo'
					<span class="upperframe"><span></span></span>
					<div class="roundframe">
						<div id="savestatus">',
							$txt['awards_saved_settings'], '
						</div>
					</div>
					<span class="lowerframe"><span></span></span>';

	echo '
					<div class="cat_bar">
						<h3 class="catbg">
							<img class="icon" src="' . $settings['images_url'] . '/awards/settings.png" alt="" />', $txt['awards_settings'], '
						</h3>
					</div>

					<br class="clear" />
					<form action="', $scripturl, '?action=admin;area=awards;sa=settings;saved=1" method="post" name="badge" id="badge" accept-charset="', $context['character_set'], '" enctype="multipart/form-data" style="padding:0; margin: 0;">
						<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
						<legend>', $txt['awards_basic_settings'], '</legend>
						<span class="upperframe"><span></span></span>
						<div class="roundframe">
						<dl class="settings">
							<dt>
								<label for="awards_dir">', $txt['awards_badges_dir'], '</label>:<br />
								<span class="smalltext">', $txt['awards_badges_dir_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="awards_dir" id="awards_dir" value="', empty($modSettings['awards_dir']) ? '' : $modSettings['awards_dir'], '" size="30" />
							</dd>

							<dt>
								<label for="awards_favorites">', $txt['awards_favorite'], '</label>:<br />
								<span class="smalltext">', $txt['awards_favorite_desc'], '</span>
							</dt>
							<dd>
								<input type="checkbox" name="awards_favorites" id="awards_favorites" ', empty($modSettings['awards_favorites']) ? '' : 'checked="checked"', ' />
							</dd>

							<dt>
								<label for="awards_in_post">', $txt['awards_in_post'], '</label>:<br />
								<span class="smalltext">', $txt['awards_in_post_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="awards_in_post" id="awards_in_post" value="', empty($modSettings['awards_in_post']) ? '0' : $modSettings['awards_in_post'], '" size="3" />
							</dd>
						</dl>
						</div>
						<span class="lowerframe"><span></span></span>
						</fieldset>

						<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
						<legend>', $txt['awards_aboveavatar_style'], '</legend>
						<span class="upperframe"><span></span></span>
						<div class="roundframe">
						<dl class="settings">
							<dt>
								<label for="awards_aboveavatar_title">', $txt['awards_aboveavatar_title'], '</label>:<br />
								<span class="smalltext">', $txt['awards_aboveavatar_title_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="awards_aboveavatar_title" id="awards_aboveavatar_title" value="', empty($modSettings['awards_aboveavatar_title']) ? '' : $modSettings['awards_aboveavatar_title'], '" size="30" />
							</dd>
							<dt>
								<label for="awards_aboveavatar_format">', $txt['awards_aboveavatar_format'], '</label>:<br />
								<span class="smalltext">', $txt['awards_aboveavatar_format_desc'], '</span>
							</dt>
							<dd>
								<select name="awards_aboveavatar_format" id="awards_aboveavatar_format">';

	$select = !empty($modSettings['awards_aboveavatar_format']) ? $modSettings['awards_aboveavatar_format'] : 0;
	foreach ($context['award_formats'] as $format)
		echo '
									<option value="', $format['id'], '"', ($format['id'] == $select) ? ' selected="selected"' : '', '>', $format['name'], '</option>';

	echo '
								</select>
							</dd>
						</dl>
						</div>
						<span class="lowerframe"><span></span></span>
						</fieldset>

						<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
						<legend>', $txt['awards_belowavatar_style'], '</legend>
						<span class="upperframe"><span></span></span>
						<div class="roundframe">
						<dl class="settings">
							<dt>
								<label for="awards_belowavatar_title">', $txt['awards_belowavatar_title'], '</label>:<br />
								<span class="smalltext">', $txt['awards_belowavatar_title_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="awards_belowavatar_title" id="awards_belowavatar_title" value="', empty($modSettings['awards_belowavatar_title']) ? '' : $modSettings['awards_belowavatar_title'], '" size="30" />
							</dd>
							<dt>
								<label for="awards_belowavatar_format">', $txt['awards_belowavatar_format'], '</label>:<br />
								<span class="smalltext">', $txt['awards_belowavatar_format_desc'], '</span>
							</dt>
							<dd>
								<select name="awards_belowavatar_format" id="awards_belowavatar_format">';

	$select = !empty($modSettings['awards_belowavatar_format']) ? $modSettings['awards_belowavatar_format'] : 0;
	foreach ($context['award_formats'] as $format)
		echo '
									<option value="', $format['id'], '"', ($format['id'] == $select) ? ' selected="selected"' : '', '>', $format['name'], '</option>';

	echo '
								</select>
							</dd>
							</dl>
						</div>
						<span class="lowerframe"><span></span></span>
						</fieldset>

						<fieldset style="border-width: 1px 0px 0px 0px; padding: 5px;">
						<legend>', $txt['awards_signature_style'], '</legend>
						<span class="upperframe"><span></span></span>
						<div class="roundframe">
						<dl class="settings">
							<dt>
								<label for="awards_signature_title">', $txt['awards_signature_title'], '</label>:<br />
								<span class="smalltext">', $txt['awards_signature_title_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="awards_signature_title" id="awards_signature_title" value="', empty($modSettings['awards_signature_title']) ? '' : $modSettings['awards_signature_title'], '" size="30" />
							</dd>
							<dt>
								<label for="awards_signature_format">', $txt['awards_signature_format'], '</label>:<br />
								<span class="smalltext">', $txt['awards_signature_format_desc'], '</span>
							</dt>
							<dd>
								<select name="awards_signature_format" id="awards_signature_format">';

	$select = !empty($modSettings['awards_signature_format']) ? $modSettings['awards_signature_format'] : 0;
	foreach ($context['award_formats'] as $format)
		echo '
									<option value="', $format['id'], '"', ($format['id'] == $select) ? ' selected="selected"' : '', '>', $format['name'], '</option>';

	echo '
								</select>
							</dd>
						</dl>
						</div>
						<span class="lowerframe"><span></span></span>
						</fieldset>

						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						<hr class="hrcolor" />
						<div class="righttext">
							<input type="submit" class="button_submit" name="save_settings" value="', $txt['save'], '" accesskey="s" />
						</div>
					</form>
				';
}

/**
 * Template for showing our category editing panel
 */
function template_edit_category()
{
	global $context, $txt, $scripturl;

	echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=editcategory" method="post" name="category" id="category" accept-charset="', $context['character_set'], '" style="padding:0; margin: 0;">
					<div class="cat_bar">
						<h3 class="catbg">
							', ((isset($_GET['saved']) && $_GET['saved'] == '1') ? $txt['awards_saved_category'] : ($context['editing'] == true ? $txt['awards_edit_category'] : $txt['awards_add_category'])), '
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
							<div class="righttext">
								<input type="hidden" name="id_category" value="', $context['category']['id'], '" />
								<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
								<input type="submit" class="button_submit" name="category_save" value="', $context['editing'] ? $txt['save'] : $txt['awards_add_category'], '" accesskey="s" />
							</div>
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</form>
				<br class="clear" />';
}

/**
 * Show all of the categorys in the system with modificaiton options
 */
function template_list_categories()
{
	global $context, $txt, $settings, $scripturl;

	echo '
				<div class="cat_bar">
					<h3 class="catbg">
						<img class="icon" src="' . $settings['images_url'] . '/awards/category.png" alt="" />&nbsp;', $txt['awards_list_categories'], '
					</h3>
				</div>
				<table class="table_grid" width="100%">
				<thead>
					<tr class="catbg">
						<th scope="col" class="first_th smalltext">', $txt['awards_actions'], '</th>
						<th scope="col" align="left" class="smalltext">', $txt['awards_category_name'], '</th>
						<th scope="col" class="last_th smalltext">', $txt['awards_num_in_category'], '</th>
					</tr>
				</thead>
				<tbody>';

	// Check if there are any categories
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
						<td width="20%" align="left">
							<a href="', $cat['edit'], '" title="', $txt['awards_button_edit'], '">[', $txt['awards_button_edit'], ']&nbsp;<img align="top" src="', $settings['images_url'], '/awards/modify.png" alt="" /></a> ', ($cat['id'] != 1) ? '
							<a href="' . $cat['delete'] . '" onclick="return confirm(\'' . $txt['awards_confirm_delete_category'] . '\');" title="' . $txt['awards_button_delete'] . '">
								[' . $txt['awards_button_delete'] . ']&nbsp;<img align="top" src="' . $settings['images_url'] . '/awards/delete.png" alt="" />
							</a>' : '', '
						</td>
						<td width="60%" align="left">
							<a href="', $cat['view'], '" title="', $cat['name'], '">', $cat['name'], '</a>
						</td>
						<td width="20%" align="center">
							', empty($cat['awards']) ? '0' : '<a href="' . $scripturl . '?action=admin;area=awards;sa=viewcategory;a_id=' . $cat['id'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '">' . $cat['awards'] . '</a>', '
						</td>
					</tr>';
		}

		echo '
					<tr class="catbg">
						<td align="right" colspan="3">
							<form class="floatright" accept-charset="ISO-8859-1" method="post" action="', $scripturl, '?action=admin;area=awards;sa=editcategory">
								<input id="add_category" class="button_submit" type="submit" value="', $txt['awards_add_category'], '" name="add_category" />
							</form>
						</td>
					</tr>';
	}

	echo '
				</tbody>
				</table>';
}

/**
 * View a single category list
 */
function template_view_category()
{
	global $context, $txt;

	if (empty($context['category']))
	{
		echo '
			<span class="upperframe"><span></span></span>
			<div class="roundframe">
				<div id="welcome">',
		$txt['awards_error_no_category'], '
				</div>
			</div>
			<span class="lowerframe"><span></span></span>';
	}
	else
	{
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
							<td align="center"><img src="', $award['img'], '" alt="', $award['award_name'], '" /></td>
							<td align="center"><img src="', $award['small'], '" alt="', $award['award_name'], '" /></td>
							<td><a href="', $award['edit'], '">', $award['award_name'], '</a></td>
							<td>', $award['description'], '</td>
						</tr>';
			}
		}

		echo '
					</tbody>
				</table>';

		// Show the pages
		echo '
				<div class="floatleft pagesection">', $txt['pages'], ': ', $context['page_index'], '</div>';
	}
}

/**
 * Template for viewing the requested awards
 */
function template_request_award()
{
	global $context, $txt, $scripturl;

	// Nothing to approve at this time?
	if (empty($context['awards']))
	{
		echo '
			<span class="upperframe"><span></span></span>
			<div class="roundframe">
				<div id="requests">',
					$txt['awards_no_requests'], '
				</div>
			</div>
			<span class="lowerframe"><span></span></span>';
	}
	else
	{
		// There be requests woohoo!
		echo '
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['awards_requests'], '
				</h3>
			</div>';

		// Start with the form.
		echo '
				<form action="', $scripturl, '?action=admin;area=awards;sa=requests" method="post" name="requests" id="requests" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">';

		// Loop through the awards
		foreach ($context['awards'] as $award)
		{
			// show this awards info in the header
			echo '
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content" align="center">
							<img style="padding:0 0 5px 0" src="', $award['img'], '" alt="', $award['award_name'], '" /><br />';

			// Small image as well?
			if ($award['img'] != $award['small'])
				echo '
							<img style="vertical-align:middle" src="', $award['small'], '" alt="', $award['award_name'], '" /> ';

			echo '
							<strong>', $award['award_name'], '</strong><br />', $award['description'], '
						</div>
						<span class="botslice"><span></span></span>
					</div>

					<div class="windowbg2">
						<span class="topslice"><span></span></span>
						<div class="content">';

			// Now output the table of members who requested this award
			echo '
							<table width="100%" class="table_grid">
								<thead>
									<tr class="titlebg">
										<th scope="col" class="first_th smalltext" width="5%"><input type="checkbox" id="checkAllMembers', $award['id'], '" checked="checked" onclick="invertAll(this, this.form, \'requests[', $award['id'], ']\');" class="check" /></th>
										<th scope="col" class="smalltext" width="25%">', $txt['who_member'], '</th>
										<th scope="col" class="last_th smalltext" width="70%">', $txt['awards_comments'], '</th>
									</tr>
								</thead>
								<tbody>';

			$alternate = true;
			foreach ($award['members'] as $id => $member)
			{
				echo '
									<tr class="', $alternate ? 'windowbg2' : 'windowbg', '">
										<td valign="middle" align="center"><input type="checkbox" name="requests[', $award['id'], '][', $id, ']" value="', $id, '" checked="checked" class="check" /></td>
										<td valign="top">
											', $member['link'], '<span class="floatright">
											', $member['pm'], '&nbsp;</span>
										</td>
										<td valign="top">
											', $member['comments'], '
										</td>
									</tr>';
				$alternate = !$alternate;
			}

			echo '
								</tbody>
							</table>
						</div>
						<span class="botslice"><span></span></span>
					</div>
					<hr class="hrcolor" />';
		}

		// Submit button
		echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<div class="righttext">
						<input type="submit" class="button_submit" name="reject_selected" value="', $txt['awards_reject_selected'], '" />
						<input type="submit" class="button_submit" name="approve_selected" value="', $txt['awards_approve_selected'], '" />
					</div>';

		// close this beast up
		echo '
				</form>
				<br class="clear" />';
	}
}