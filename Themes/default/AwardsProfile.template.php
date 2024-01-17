<?php

/**
 * @name      Awards Modification
 * @license   Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version   3.1.0
 *
 * Original Software by:           Juan "JayBachatero" Hernandez
 * Copyright (c) 2006-2009:        YodaOfDarkness (Fustrate)
 * Copyright (c) 2010:             Jason "JBlaze" Clemons
 *
 */

/**
 * This is the template for showing a members awards from the profile view
 */
function template_awards()
{
	global $context, $txt, $settings;

	echo '
					<div class="cat_bar">
						<h3 class="catbg">
							', $txt['awards'], '
						</h3>
					</div>';

	// Show the amount of awards that a member has
	if (!empty($context['count_awards']))
	{
		echo '
					<p class="description">',
						sprintf($txt['awards_count_badges'], $context['count_awards']), '
					</p>';
	}

	// Check if this member has any awards
	if (empty($context['categories']))
	{
		echo '
					<span class="upperframe"><span></span></span>
					<div class="roundframe">
						<div id="noawardsforyou">',
							$context['user']['is_owner'] ? $txt['awards_no_awards_member'] : sprintf($txt['awards_no_awards_this_member'], $context['member']['name']), '
						</div>
					</div>
					<span class="lowerframe"><span></span></span>';
	}
	else
	{
		// There be awards !!, output them by category for viewing
		foreach ($context['categories'] as $category)
		{
			echo '
						<div class="cat_bar">
							<h3 class="catbg">
								<img class="icon" src="' . $settings['images_url'] . '/awards/category.png" alt="" />&nbsp;', $txt['awards_category'], ': ', $category['name'], '
							</h3>
						</div>
						<table class="table_grid" style="width:100%">
						<thead>
							<tr class="catbg">
								<th scope="col" class="first_th smalltext" style="width:17%">', $txt['awards_image'], '</th>
								<th scope="col" class="smalltext" style="width:8%">', $txt['awards_mini'], '</th>
								<th scope="col" class="smalltext" style="width:20%">', $txt['awards_name'], '</th>
								<th scope="col" class="smalltext" style="width:17%">', $txt['awards_date'], '</th>
								<th scope="col" class="smalltext">', $txt['awards_details'], '</th>
								<th scope="col" class="last_th smalltext" style="vertical-align:middle;width:5%">', $txt['awards_favorite2'], '</th>
							</tr>
						</thead>
						<tbody>';

			$which = true;

			// Output the awards for this category
			foreach ($category['awards'] as $award)
			{
				$which = !$which;
				echo '
						<tr class="windowbg', $which ? '2' : '', $award['favorite']['fav'] == 1 ? ' favorite' : '', '">
							<td style="vertical-align:middle" class="centertext">
								<a href="', $award['more'], '">
									<img src="', $award['img'], '" alt="', $award['award_name'], '" />
								</a>
							</td>
							<td style="vertical-align:middle" class="centertext">
								<a href="', $award['more'], '">
									<img src="', $award['small'], '" alt="', $award['award_name'], '" />
								</a>
							</td>
							<td>
								', $award['award_name'], '
							</td>
							<td>
								', $txt['months'][$award['time'][1]], ' ', $award['time'][2], ', ', $award['time'][0], '
							</td>
							<td>',
								$award['description'], '
							</td>
							<td class="centertext">',
								$award['favorite']['fav'] == 1
									? '<img src="' . $settings['images_url'] . '/awards/star.png" alt="' . $txt['awards_favorite'] . '" />'
									: '',
								$context['allowed_fav'] && $award['favorite']['allowed']
									? '<a href="' . $award['favorite']['href'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '">' . $award['favorite']['img'] . '</a>'
									: '', '
							</td>
						</tr>';
			}

			echo '
					</tbody>
				</table>
				<br class="clear" />';
		}

		// Show the pages
		echo '
					<span class="smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>';
	}
}

/**
 * Template for showing all members that have a certain award
 */
function template_awards_members()
{
	global $context, $txt, $settings;

	// Open the Div
	echo '
		<div class="title_bar">
			<h3 class="titlebg">
				<img class="icon" src="' . $settings['images_url'] . '/awards/award.png" alt="" />&nbsp;', $txt['viewingAwards'] . ' ' . $context['award']['award_name'], '
			</h3>
		</div>

		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content centertext">
				<img style="padding:0 0 5px 0" src="', $context['award']['img'], '" alt="', $context['award']['award_name'], '" /><br />';

	if ($context['award']['img'] !== $context['award']['small'])
	{
		echo '
				<img style="vertical-align:middle" class="centertext" src="', $context['award']['small'], '" alt="', $context['award']['award_name'], '" /> ';
	}

	echo '
				<strong>', $context['award']['award_name'], '</strong>
				<br />', $context['award']['description'], '
			</div>
			<span class="botslice"><span></span></span>
		</div>
		<br class="clear" />';

	// Show the list output
	template_show_list('view_profile_assigned');

	echo '
		<br class="clear" />';
}

/**
 * Template for showing available awards
 */
function template_awards_list()
{
	global $context, $txt, $settings;

	echo '
				<div class="cat_bar">
					<h3 class="catbg">
						', $txt['awards_title'], '
					</h3>
				</div>
				<br class="clear" />';

	// Check if there are any awards
	if (empty($context['categories']))
	{
		echo '
				<span class="upperframe"><span></span></span>
				<div class="roundframe">
					<div id="nostinkinbadges">',
		$txt['awards_error_no_badges'], '
					</div>
				</div>
				<span class="lowerframe"><span></span></span>';
	}
	else
	{
		foreach ($context['categories'] as $key => $category)
		{
			echo '
					<div class="title_bar">
						<h3 class="titlebg">
							<img class="icon" src="' . $settings['images_url'] . '/awards/category.png" alt="" />&nbsp;', '<a href="', $category['view'], '">', $txt['awards_category'], ': ', $category['name'], '</a>
						</h3>
					</div>
					<table class="table_grid" style="width:100%">
					<thead>
						<tr class="catbg">
							<th scope="col" class="first_th smalltext" style="width:15%">', $txt['awards_image'], '</th>
							<th scope="col" class="smalltext" style="width:15%">', $txt['awards_mini'], '</th>
							<th scope="col" class="smalltext" style="width:25%">', $txt['awards_name'], '</th>
							<th scope="col" class="smalltext" style="width:40%">', $txt['awards_details'], '</th>
							<th scope="col" class="last_th smalltext" style="width:5%">', $txt['awards_actions'], '</th>
						</tr>
					</thead>
					<tbody>';

			$which = false;

			foreach ($category['awards'] as $award)
			{
				$which = !$which;
				echo '
						<tr class="windowbg', $which ? '2' : '', '">
							<td style="vertical-align:middle" class="centertext">
								<img src="', $award['img'], '" alt="', $award['award_name'], '" />
							</td>
							<td style="vertical-align:middle" class="centertext">
								<img src="', $award['small'], '" alt="', $award['award_name'], '" />
							</td>
							<td>', $award['award_name'], '</td>
							<td>', $award['description'], '</td>
							<td style="vertical-align:middle" class="smalltext">
								<a href="', $award['view_assigned'], '">
									<img src="', $settings['images_url'], '/awards/user.png" title="', $txt['awards_button_members'], '" alt="" />
								</a>';

				if (!empty($award['requestable']))
				{
					echo '
								<a href="', $award['requestable_link'], '">
									<img src="', $settings['images_url'], '/awards/award_request.png" title="', $txt['awards_request_award'], '" alt="" />
								</a>';
				}

				echo '
							</td>
						</tr>';
			}

			echo '
					</tbody>
					</table>
				<br class="clear" />';
		}

		// Show the pages
		echo '
				<span class="smalltext">', $txt['pages'], ': ', $context['page_index'], '</span>';
	}
}

/**
 * Template for showing a list of requestable awards
 */
function template_awards_request()
{
	global $context, $scripturl, $txt, $settings;

	// Open the Header
	echo '
		<div class="title_bar">
			<h3 class="titlebg">
				<img class="icon" src="' . $settings['images_url'] . '/awards/award.png" alt="" />&nbsp;', $txt['awards_requesting_award'] . ' ' . $context['award']['award_name'], '
			</h3>
		</div>

		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="content centertext">
				<img style="padding:0 0 5px 0" src="', $context['award']['img'], '" alt="', $context['award']['award_name'], '" /><br />';

	if ($context['award']['img'] !== $context['award']['small'])
	{
		echo '
				<img style="vertical-align:middle" class="centertext" src="', $context['award']['small'], '" alt="', $context['award']['award_name'], '" /> ';
	}

	echo '
				<strong>', $context['award']['award_name'], '</strong><br />', $context['award']['description'], '
			</div>
			<span class="botslice"><span></span></span>
		</div>
		<br class="clear" />';

	// Start with the form.
	echo '
		<form action="', $scripturl, '?action=profile;area=requestAwards;step=2" method="post" name="request" id="request" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">';

	// Enter a reason why you want this award.
	echo '
			<table style="width:100%" class="table_grid">
				<thead>
					<tr class="titlebg">
						<th scope="col" class="first_th smalltext">', $txt['awards_request_comments'], '</th>
						<th scope="col" class="last_th smalltext"></th>
					</tr>
				</thead>
				<tbody>
					<tr class="windowbg2">
						<td colspan="2" style="vertical-align:middle">
							<div style="margin-bottom: 2ex;">
								<textarea cols="75" rows="7" style="', $context['browser']['is_ie8'] ? 'max-width: 100%; min-width: 100%' : 'width: 100%', '; height: 100px;" name="comments" tabindex="', $context['tabindex']++, '"></textarea><br />
							</div>
						</td>
					</tr>
				</tbody>
			</table>';

	// add in a submit button and close the form
	echo '
			<div class="floatright padding">
				<input class="button_submit" type="submit" name="request" value="', $txt['awards_request_award'], '" />
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="id_award" value="', $context['award']['id'], '" />
		</form>
		<br class="clear" />';
}
