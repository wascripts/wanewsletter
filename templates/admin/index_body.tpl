<!-- BEGIN check_update -->
<script>
<!--
document.addEventListener('DOMContentLoaded', function() {
	document.getElementById('check-update').addEventListener('click', function(evt) {
		evt.preventDefault();

		var mainBlock = document.getElementById('check-update').parentNode;
		mainBlock.replaceChild(loadingImg, document.getElementById('check-update'));

		var xhr = new XMLHttpRequest();
		xhr.onload = function() {
			var result = JSON.parse(xhr.responseText);

			var strong = document.createElement('strong');
			mainBlock.replaceChild(strong, loadingImg);

			switch (result.code) {
				case '0':
					strong.textContent = '{check_update.L_VERSION_UP_TO_DATE}';
					break;
				case '1':
					strong.textContent = '{check_update.L_NEW_VERSION_AVAILABLE}';
					strong.style.color = 'hsl(140, 70%, 40%)';

					mainBlock.appendChild(document.createTextNode(' \u2013 '));

					var link = document.createElement('a');
					link.setAttribute('href', '{check_update.U_DOWNLOAD_PAGE}');
					link.textContent = '{check_update.L_DOWNLOAD_PAGE}';
					mainBlock.appendChild(link);
					break;
				case '2':
				default:
					strong.textContent = '{check_update.L_SITE_UNREACHABLE}';
					strong.style.color = 'hsl(0, 70%, 40%)';
					break;
			}
		};
		xhr.open('GET', evt.target.href + '&output=json', true);
		xhr.send();
	}, false);

	// Image de chargement
	var loadingImg = document.createElement('img');
	loadingImg.setAttribute('src', '../templates/images/loading.gif');
	loadingImg.setAttribute('alt', 'Loading\u2026');
	loadingImg.style.verticalAlign = 'middle';
	loadingImg.style.lineHeight = '1';
}, false);
//-->
</script>
<!-- END check_update -->

<p id="explain">{L_EXPLAIN}</p>

<div class="block">
	<h2>{TITLE_HOME}</h2>

	<ul id="home">
		<li>{REGISTERED_SUBSCRIBERS}</li>
		<li>{TEMP_SUBSCRIBERS}</li>
		<li>{NEWSLETTERS_SENDED}</li>
		<!-- BEGIN switch_last_newsletter -->
		<li>{switch_last_newsletter.DATE_LAST_NEWSLETTER}</li>
		<!-- END switch_last_newsletter -->
		<li>{L_DBSIZE}&nbsp;: <b>{DBSIZE}</b></li>
		<li>{L_FILESIZE}&nbsp;: <b>{FILESIZE}</b></li>
		<li>{USED_VERSION} &ndash;
			<!-- BEGIN version_up_to_date -->
			<strong>{version_up_to_date.L_VERSION_UP_TO_DATE}</strong>
			<!-- END version_up_to_date -->
			<!-- BEGIN new_version_available -->
			<strong style="color: hsl(140, 70%, 40%);">{new_version_available.L_NEW_VERSION_AVAILABLE}</strong>
			&ndash; <a href="{new_version_available.U_DOWNLOAD_PAGE}">{new_version_available.L_DOWNLOAD_PAGE}</a>
			<!-- END new_version_available -->
			<!-- BEGIN check_update -->
			<a id="check-update" href="tools.php?mode=check_update">{check_update.L_CHECK_UPDATE}</a>
			<!-- END check_update -->
		</li>
	</ul>
</div>

{LISTBOX}

