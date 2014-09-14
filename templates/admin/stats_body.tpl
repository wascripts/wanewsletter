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
	
	getPrevPeriod: function() {
		
		if( this.ready == true ) {
			this.currentDate.setMonth(this.currentDate.getMonth()-1);
			
			this.graphBox.className = 'transitionToPrev';
			this.graphBox.insertBefore(this.graphBox.lastElementChild, this.graphBox.firstElementChild);
			
			this._getPeriod();
		}
	},
	
	getNextPeriod: function() {
		
		if( this.ready == true ) {
			this.currentDate.setMonth(this.currentDate.getMonth()+1);
			
			this.graphBox.className = 'transitionToNext';
			this.graphBox.appendChild(this.graphBox.firstElementChild);
			
			this._getPeriod();
		}
	},
	
	_getPeriod: function() {
		var firstYear = Number(this.form.elements['year'].options[0].value);
		var currentYear = Number(this.currentDate.getFullYear());
		var lastYear = Number(this.form.elements['year'].options[this.form.elements['year'].length-1].value);
		
		var opt = document.createElement('option');
		opt.setAttribute('value', currentYear);
		opt.textContent = currentYear;
		
		if( currentYear < firstYear ) {
			this.form.elements['year'].add(opt, this.form.elements['year'].options[0]);
			firstYear = currentYear;
		}
		else if( currentYear > lastYear ) {
			this.form.elements['year'].add(opt);
		}
		
		this.form.elements['year'].options[currentYear - firstYear].selected = true;
		this.form.elements['month'].options[this.currentDate.getMonth()].selected = true;
		
		this.ready = false;
		
		var handleEvent = function() {
			Stats.preloadAndSync(false);
		};
		
		this.graphBox.addEventListener('webkitTransitionEnd', handleEvent, false);
		this.graphBox.addEventListener('transitionend', handleEvent, false);
		window.setTimeout(function(){ Stats.preloadAndSync(false); }, 700);/* Compatibilité avec IE9 */
	},
	
	preloadAndSync: function(force) {
		if( this.ready == false || force == true ) {
			var imgList = this.graphBox.getElementsByTagName('img');
			
			this.graphBox.className = '';
			imgList[0].className = 'prev';
			imgList[1].className = 'current';
			imgList[2].className = 'next';
			
			// preload des images précédentes et suivantes
			var prev = new Image();
			prev.src = this.getURL(new Date(this.currentDate.getFullYear(), this.currentDate.getMonth()-1, 1));
			imgList[0].setAttribute('src', prev.src);
			
			var next = new Image();
			next.src = this.getURL(new Date(this.currentDate.getFullYear(), (this.currentDate.getMonth()+1), 1));
			imgList[2].setAttribute('src', next.src);
			
			// mise à jour liens
			this.graphBox.prevLink.setAttribute('href', prev.src.replace('img=graph&', ''));
			this.graphBox.nextLink.setAttribute('href', next.src.replace('img=graph&', ''));
			
			this.ready = true;
		}
	},
	
	getURL: function(date) {
		return './stats.php?img=graph&year='+date.getFullYear()+'&month='+(date.getMonth()+1);
	},
	
	initialize: function() {
		this.graphBox = document.getElementById('graph-box');
		this.graphBox.prevLink = document.getElementById('prev-period');
		this.graphBox.nextLink = document.getElementById('next-period');
		this.form = document.forms['date-form'];
		
		this.currentDate = new Date(
			this.form.elements['year'].value,
			(Number(this.form.elements['month'].value)-1),
			1
		);
		
		var img = this.graphBox.firstElementChild.cloneNode(false);
		this.graphBox.insertBefore(img, this.graphBox.firstElementChild);
		
		img = this.graphBox.firstElementChild.cloneNode(false);
		this.graphBox.appendChild(img);
		
		this.preloadAndSync(false);
		
		this.form.addEventListener('submit', function(evt){
			Stats.currentDate.setFullYear(this.elements['year'].value);
			Stats.currentDate.setMonth(this.elements['month'].value);
			
			Stats.preloadAndSync(true);
			Stats.getPrevPeriod();
			
			evt.preventDefault();
		}, false);
		
		this.graphBox.prevLink.addEventListener('click', function(evt) {
			Stats.getPrevPeriod();
			evt.preventDefault();
		}, false);
		this.graphBox.nextLink.addEventListener('click', function(evt) {
			Stats.getNextPeriod();
			evt.preventDefault();
		}, false);
	}
};

document.addEventListener('DOMContentLoaded', function() { Stats.initialize(); }, false);
//-->
</script>

<form id="date-form" method="get" action="./stats.php">
<div class="block">
	<h2>{L_TITLE}</h2>
	
	<p class="explain">{L_EXPLAIN_STATS}</p>
	
	<div class="bottom">
		<select name="year">{YEAR_LIST}</select>
		<select name="month">{MONTH_LIST}</select>
		{S_HIDDEN_FIELDS} <button type="submit" class="primary">{L_GO_BUTTON}</button>
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
		<a id="prev-period" href="{U_PREV_PERIOD}">{L_PREV_PERIOD}</a>
		&ndash;
		<a id="next-period" href="{U_NEXT_PERIOD}">{L_NEXT_PERIOD}</a>
	</div>
</div>

<div class="stats">
	<img src="{U_IMG_CAMEMBERT}" alt="" title="{L_IMG_CAMEMBERT}" />
</div>

{LISTBOX}