<script>
<!--
function sendMail()
{
	var loadingIcon = document.getElementById('loading-icon');
	var messageBox  = document.querySelector('p.message').firstChild;

	loadingIcon.style.display = 'inline-block';
	messageBox.textContent = "{L_PROCESS}";

	var xhr = new XMLHttpRequest();
	xhr.open('GET', 'envoi.php?mode=progress&id={LOG_ID}&output=json');
	xhr.onload = function () {
		var data = JSON.parse(xhr.responseText);
		var bar  = document.querySelector('progress');

		if (!data.error) {
			bar.value = data.total_sent;
			bar.title = data.percent + ' %';
			bar.textContent = data.percent + ' %';

			if (data.total_to_send > 0) {
				window.setTimeout(sendMail, {SENDING_DELAY} * 1000);
			}
			// Tous les emails ont été envoyés. On le notifie à l’utilisateur.
			else if (window.Notification && Notification.permission === "granted") {
				var n = new Notification(data.message);
			}
		}
		else {
			messageBox.style.fontWeight = 'bold';
			messageBox.style.color = '#600';
		}

		loadingIcon.style.display = 'none';
		messageBox.textContent = data.message;
	};
	xhr.send(null);
}

document.addEventListener('DOMContentLoaded', function () {
	sendMail();
}, false);
//-->
</script>

<style>
p.message   { line-height: 2em; }
p.message * { vertical-align: middle; }
#loading-icon { display: none; }
</style>

<div class="block">
	<h2>{L_SENDING_NL}</h2>

	<p class="message"><span>{L_NEXT_SEND}</span>
	<img id="loading-icon" src="../templates/images/loading.gif" alt=""><br>
	<progress value="{TOTAL_SENT}" max="{TOTAL}" title="{SENT_PERCENT}&nbsp;%">{SENT_PERCENT}&nbsp;%</progress></p>
</div>
