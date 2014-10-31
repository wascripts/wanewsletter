<script>
<!--
var Stats = {
	/**
	 * Lors de la manipulation des dates, ne pas oublier que Javascript traite
	 * les valeurs numériques des mois sur un intervalle 0..11 alors que le
	 * script stats.php, la selectbox 'month' et dans les liens #prev-period et
	 * #next-period, les mois sont dans un intervalle 1..12
	 */
	currentDate: null,
	graphBox: null,
	ready: false,

	setPrevPeriod: function(prevDate) {
		var prevImg = this.graphBox.firstElementChild;
		var prevURL = this.getURL(prevDate);
		var prev = new Image();
		prev.src = prevURL.replace('?', '?img=graph&');
		prevImg.setAttribute('src', prev.src);

		this.graphBox.prevLink.setAttribute('href', prevURL);
		this.graphBox.prevLink.setAttribute('title', this.getTitle(prevDate));
	},

	getPrevPeriod: function(prevDate) {

		if (this.ready) {
			this.setPrevPeriod(prevDate);
			this.currentDate = prevDate;

			this.graphBox.className = 'transitionToPrev';
			this.graphBox.insertBefore(this.graphBox.lastElementChild, this.graphBox.firstElementChild);

			this._getPeriod();
		}
	},

	setNextPeriod: function(nextDate) {
		var nextImg = this.graphBox.lastElementChild;
		var nextURL = this.getURL(nextDate);
		var next = new Image();
		next.src = nextURL.replace('?', '?img=graph&');
		nextImg.setAttribute('src', next.src);

		this.graphBox.nextLink.setAttribute('href', nextURL);
		this.graphBox.nextLink.setAttribute('title', this.getTitle(nextDate));
	},

	getNextPeriod: function(nextDate) {

		if (this.ready) {
			this.setNextPeriod(nextDate);
			this.currentDate = nextDate;

			this.graphBox.className = 'transitionToNext';
			this.graphBox.appendChild(this.graphBox.firstElementChild);

			this._getPeriod();
		}
	},

	_getPeriod: function() {
		var firstYear   = Number(this.form.elements['year'].options[0].value);
		var currentYear = Number(this.currentDate.getFullYear());
		var lastYear    = Number(this.form.elements['year'].options[this.form.elements['year'].length-1].value);

		var opt = document.createElement('option');
		opt.setAttribute('value', currentYear);
		opt.textContent = currentYear;

		if (currentYear < firstYear) {
			this.form.elements['year'].add(opt, this.form.elements['year'].options[0]);
			firstYear = currentYear;
		}
		else if (currentYear > lastYear) {
			this.form.elements['year'].add(opt);
		}

		this.form.elements['year'].options[currentYear - firstYear].selected = true;
		this.form.elements['month'].options[this.currentDate.getMonth()].selected = true;

		this.ready = false;

		var handleEvent = function() {
			Stats.preloadAndSync();
		};

		this.graphBox.addEventListener('webkitTransitionEnd', handleEvent, false);
		this.graphBox.addEventListener('transitionend', handleEvent, false);
		window.setTimeout(function() { Stats.preloadAndSync(); }, 700);/* Compatibilité avec IE9 */
	},

	preloadAndSync: function() {
		if (!this.ready) {
			var imgList = this.graphBox.getElementsByTagName('img');

			this.graphBox.className = '';
			imgList[0].className = 'prev';
			imgList[1].className = 'current';
			imgList[2].className = 'next';

			this.setPrevPeriod(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()-1, 1));
			this.setNextPeriod(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()+1, 1));

			this.ready = true;
		}
	},

	getURL: function(date) {
		return this.aURL
			.replace('?img=graph&',  '?')
			.replace(/year=[0-9]+/,  'year='+date.getFullYear())
			.replace(/month=[0-9]+/, 'month='+(date.getMonth()+1));
	},

	getTitle: function(date) {
		var title = String(date.getFullYear()) + '/' + String(date.getMonth()+1);

		// On détecte l'usage avancé possible de toLocaleString() en passant
		// un argument volontairement erroné et en testant le type d'erreur
		try {
			date.toLocaleDateString("i");
		}
		catch (e) {
			if (e.name === "RangeError") {
				title = date.toLocaleDateString(document.documentElement.lang, { month: "long", year: "numeric" });
			}
		}

		return this.aTitle + ' \u2013 ' + title;
	},

	pushHistory: function() {
		window.history.pushState(
			{ year: this.currentDate.getFullYear(), month: this.currentDate.getMonth() },
			this.getTitle(this.currentDate),
			this.getURL(this.currentDate)
		);
 	},

	initialize: function() {
		this.graphBox = document.getElementById('graph-box');
		this.graphBox.prevLink = document.getElementById('prev-period');
		this.graphBox.nextLink = document.getElementById('next-period');
		this.form = document.forms['date-form'];

		// Modèles pour les titres et URL des liens et des entrées dans l'historique
		this.aTitle = this.graphBox.prevLink.getAttribute('title');
		this.aTitle = this.aTitle.substring(0, this.aTitle.indexOf('\u2013')-1);
		this.aURL   = this.graphBox.firstElementChild.getAttribute('src');

		this.currentDate = new Date(
			this.form.elements['year'].value,
			(Number(this.form.elements['month'].value)-1),
			1
		);

		var img = this.graphBox.firstElementChild.cloneNode(false);
		this.graphBox.insertBefore(img, this.graphBox.firstElementChild);

		img = this.graphBox.firstElementChild.cloneNode(false);
		this.graphBox.appendChild(img);

		this.preloadAndSync();

		// Formulaire de choix de période
		this.form.addEventListener('submit', function(evt){
			var newDate = new Date(this.elements['year'].value, (this.elements['month'].value-1), 1);

			if (newDate.getTime() < Stats.currentDate.getTime()) {
				Stats.getPrevPeriod(newDate);
			}
			else {
				Stats.getNextPeriod(newDate);
			}

			Stats.pushHistory();
			evt.preventDefault();
		}, false);

		// Liens précédent et suivant
		this.graphBox.prevLink.addEventListener('click', function(evt) {
			Stats.getPrevPeriod(new Date(Stats.currentDate.getFullYear(), Stats.currentDate.getMonth()-1, 1));
			Stats.pushHistory();
			evt.preventDefault();
		}, false);
		this.graphBox.nextLink.addEventListener('click', function(evt) {
			Stats.getNextPeriod(new Date(Stats.currentDate.getFullYear(), Stats.currentDate.getMonth()+1, 1));
			Stats.pushHistory();
			evt.preventDefault();
		}, false);

		// Gestion de l'historique du navigateur
		if (window.history && window.history.pushState) {
			window.history.replaceState(
				{ year: this.currentDate.getFullYear(), month: this.currentDate.getMonth() },
				this.getTitle(this.currentDate),
				this.getURL(this.currentDate)
			);

			window.addEventListener('popstate', function(evt) {
				if (evt.state) {
					var newDate = new Date(evt.state.year, evt.state.month, 1);

					if (newDate.getTime() < Stats.currentDate.getTime()) {
						Stats.getPrevPeriod(newDate);
					}
					else {
						Stats.getNextPeriod(newDate);
					}
				}
			}, false);
		}
		else {
			this.pushHistory = function() { };
		}
	}
};

document.addEventListener('DOMContentLoaded', function() { Stats.initialize(); }, false);
//-->
</script>

<form id="date-form" method="get" action="./stats.php">
<div class="block">
	<h2>{L_TITLE}</h2>

	<div class="explain">{L_EXPLAIN_STATS}</div>

	<div class="bottom">
		<select name="year">{YEAR_LIST}</select>
		<select name="month">{MONTH_LIST}</select>
		<button type="submit" class="primary">{L_GO_BUTTON}</button>
	</div>
</div>
</form>

<!-- BEGIN statsdir_error -->
<p class="warning"><strong>{statsdir_error.MESSAGE}</strong></p>
<!-- END statsdir_error -->

<div class="stats">
	<div id="graph-box">
		<img src="{U_IMG_GRAPH}" alt="" title="{L_IMG_GRAPH}" class="current" />
	</div>
	<div>
		<a id="prev-period" href="{U_PREV_PERIOD}" title="{L_PREV_TITLE}">{L_PREV_PERIOD}</a>
		&ndash;
		<a id="next-period" href="{U_NEXT_PERIOD}" title="{L_NEXT_TITLE}">{L_NEXT_PERIOD}</a>
	</div>
</div>

<div class="stats">
	<img src="stats.php?img=camembert" alt="" title="{L_IMG_CAMEMBERT}" />
</div>

{LISTBOX}