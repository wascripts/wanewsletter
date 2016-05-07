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
			bar.title = data.percent + ' %';
			bar.textContent = data.percent + ' %';

			var updateBar = window.setInterval(function () {
				var value = parseFloat(bar.value) + (data.total_sent / 100);
				if (value > bar.max) {
					value = bar.max;
				}

				bar.value = value;

				if (parseInt(bar.value) == data.total_sent) {
					window.clearInterval(updateBar);
				}
			}, 10);

			if (data.total_to_send > 0) {
				window.setTimeout(sendMail, getDelay(data.next_sending_ts));
			}
			// Tous les emails ont été envoyés. On le notifie à l’utilisateur.
			else if (window.Notification && Notification.permission === "granted") {
				var opts = {};
				var message = data.message;
				if (message.indexOf('\n') != -1) {
					opts.body = message.substring(message.indexOf('\n')+1);
					message = message.substring(0, message.indexOf('\n'));
				}
				var n = new Notification(message, opts);
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

function getDelay(ts)
{
	return (((ts + 1)*1000) - Date.now());
}

document.addEventListener('DOMContentLoaded', function () {
	window.setTimeout(sendMail, getDelay({NEXT_SENDING_TS}));
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

	<p class="message"><span>{MESSAGE}</span>
	<img id="loading-icon" src="../templates/images/loading.gif" alt=""><br>
	<progress value="{TOTAL_SENT}" max="{TOTAL}" title="{SENT_PERCENT}&nbsp;%">{SENT_PERCENT}&nbsp;%</progress></p>
</div>
