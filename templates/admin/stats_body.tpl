<script type="text/javascript">
<!--
var Stats = {
	currentDate: null,
	
	getPrevPeriod: function() {
		
		if( document.getElementById('graph2').className == '' ) {
			document.getElementById('graph-box').removeChild(document.getElementById('graph2'));
			document.getElementById('graph1').className = 'toLeft';
			document.getElementById('graph1').setAttribute('id', 'graph2');
			
			var tmp = document.getElementById('graph2').cloneNode(true);
			tmp.setAttribute('id', 'graph1');
			tmp.className = '';
			document.getElementById('graph-box').insertBefore(tmp, document.getElementById('graph-box').firstChild);
		}
		
		window.setTimeout(function(){Stats.getPrevPeriod2();}, 50);
	},
	
	getPrevPeriod2: function() {
		this.currentDate.setMonth(this.currentDate.getMonth()-1);
		var sUrl = this.getURL(this.currentDate.getFullYear(), (this.currentDate.getMonth()+1));
		document.getElementById('graph1').firstChild.src = sUrl;
		document.getElementById('graph2').className = '';
		this.preloadAndSync();
	},
	
	getNextPeriod: function() {
		
		if( document.getElementById('graph2').className == 'toLeft' ) {
			document.getElementById('graph-box').removeChild(document.getElementById('graph1'));
			document.getElementById('graph2').setAttribute('id', 'graph1');
			document.getElementById('graph1').className = '';
			
			var tmp = document.getElementById('graph1').cloneNode(true);
			tmp.setAttribute('id', 'graph2');
			document.getElementById('graph-box').appendChild(tmp);
		}
		
		window.setTimeout(function(){Stats.getNextPeriod2();}, 50);
	},
	
	getNextPeriod2: function() {
		this.currentDate.setMonth(this.currentDate.getMonth()+1);
		var sUrl = this.getURL(this.currentDate.getFullYear(), (this.currentDate.getMonth()+1));
		document.getElementById('graph2').firstChild.src = sUrl;
		document.getElementById('graph2').className = 'toLeft';
		this.preloadAndSync();
	},
	
	preloadAndSync: function() {
		// preload des images précédentes et suivantes
		var prev = new Image();
		prev.src = this.getURL(this.currentDate.getFullYear(), this.currentDate.getMonth());
		
		var next = new Image();
		next.src = this.getURL(this.currentDate.getFullYear(), (this.currentDate.getMonth()+2));
		
		// mise à jour du formulaire
		var form = document.forms['date-form'];
		form.elements['month'].options[this.currentDate.getMonth()].selected = true;
		
		var indexYear = Number(this.currentDate.getFullYear())-Number(form.elements['year'].options[0].value);
		if( indexYear > 0 && indexYear < form.elements['year'].options.length ) {
			form.elements['year'].options[indexYear].selected = true;
		}
		
		// mise à jour liens
		document.getElementById('prev-period').setAttribute('href', prev.src.replace('img=graph&', ''));
		document.getElementById('next-period').setAttribute('href', next.src.replace('img=graph&', ''));
	},
	
	getURL: function(year, month) {
		return './stats.php?img=graph&year='+year+'&month='+month;
	},
	
	initialize: function() {
		this.currentDate = new Date(
			document.getElementsByName('year')[0].value,
			(Number(document.getElementsByName('month')[0].value)-1),
			1);
		this.preloadAndSync();
		
		DOM_Events.addListener('submit', function(evt){
			var form = document.forms['date-form'];
			Stats.currentDate.setMonth(form.elements['month'].value);
			Stats.currentDate.setFullYear(form.elements['year'].value);
			Stats.getPrevPeriod();
			
			evt.preventDefault();
		}, false, document.forms['date-form']);
		
		DOM_Events.addListener('click', function(evt){
			Stats.getPrevPeriod();
			evt.preventDefault();
		}, false, document.getElementById('prev-period'));
		DOM_Events.addListener('click', function(evt){
			Stats.getNextPeriod();
			evt.preventDefault();
		}, false, document.getElementById('next-period'));
	}
};

DOM_Events.addListener('load', function(){Stats.initialize();}, false, document);
//-->
</script>

<form id="date-form" method="get" action="./stats.php">
<div class="bloc">
	<h2>{L_TITLE}</h2>
	
	<table class="content">
		<tr>
			<td class="explain">{L_EXPLAIN_STATS}</td>
		</tr>
	</table>
	
	<div class="bottom">
		<select name="year">{YEAR_LIST}</select>
		<select name="month">{MONTH_LIST}</select>
		{S_HIDDEN_FIELDS} <input type="submit" value="{L_GO_BUTTON}" class="pbutton" />
	</div>
</div>
</form>

<!-- BEGIN statsdir_error -->
<p class="warning"><strong>{statsdir_error.MESSAGE}</strong></p>
<!-- END statsdir_error -->

<div class="stats">
	<div id="graph-box">
		<span id="graph1"><img src="{U_IMG_GRAPH}" alt="" title="{L_IMG_GRAPH}" /></span>
		<span id="graph2"><img src="{U_IMG_GRAPH}" alt="" title="{L_IMG_GRAPH}" /></span>
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