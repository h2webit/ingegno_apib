<?php

$this->load->model('contabilita/conteggi');

$anno = (!empty($this->input->get('anno'))) ? $this->input->get('anno') : date('Y');

$getFatturatoAnnoMensile = $this->conteggi->getFatturatoAnnoMensile($anno);
$getSpeseAnnoMensile = $this->conteggi->getSpeseAnnoMensile($anno);


// Trimestri
$fatturato[1] = @$getFatturatoAnnoMensile[1]['imponibile']+ @$getFatturatoAnnoMensile[2]['imponibile']+ @$getFatturatoAnnoMensile[3]['imponibile'];
$fatturato[2] = @$getFatturatoAnnoMensile[4]['imponibile']+ @$getFatturatoAnnoMensile[5]['imponibile']+ @$getFatturatoAnnoMensile[6]['imponibile'];
$fatturato[3] = @$getFatturatoAnnoMensile[7]['imponibile']+ @$getFatturatoAnnoMensile[8]['imponibile']+ @$getFatturatoAnnoMensile[9]['imponibile'];
$fatturato[4] = @$getFatturatoAnnoMensile[10]['imponibile']+ @$getFatturatoAnnoMensile[11]['imponibile']+ @$getFatturatoAnnoMensile[12]['imponibile'];

$fatturato_iva[1] = @$getFatturatoAnnoMensile[1]['iva']+ @$getFatturatoAnnoMensile[2]['iva']+ @$getFatturatoAnnoMensile[3]['iva'];
$fatturato_iva[2] = @$getFatturatoAnnoMensile[4]['iva']+ @$getFatturatoAnnoMensile[5]['iva']+ @$getFatturatoAnnoMensile[6]['iva'];
$fatturato_iva[3] = @$getFatturatoAnnoMensile[7]['iva']+ @$getFatturatoAnnoMensile[8]['iva']+ @$getFatturatoAnnoMensile[9]['iva'];
$fatturato_iva[4] = @$getFatturatoAnnoMensile[10]['iva']+ @$getFatturatoAnnoMensile[11]['iva']+ @$getFatturatoAnnoMensile[12]['iva'];

$spese[1] = $getSpeseAnnoMensile[1]['imponibile']+$getSpeseAnnoMensile[2]['imponibile']+$getSpeseAnnoMensile[3]['imponibile'];
$spese[2] = $getSpeseAnnoMensile[4]['imponibile']+$getSpeseAnnoMensile[5]['imponibile']+$getSpeseAnnoMensile[6]['imponibile'];
$spese[3] = $getSpeseAnnoMensile[7]['imponibile']+$getSpeseAnnoMensile[8]['imponibile']+$getSpeseAnnoMensile[9]['imponibile'];
$spese[4] = $getSpeseAnnoMensile[10]['imponibile']+$getSpeseAnnoMensile[11]['imponibile']+$getSpeseAnnoMensile[12]['imponibile'];

$spese_iva[1] = $getSpeseAnnoMensile[1]['iva']+$getSpeseAnnoMensile[2]['iva']+$getSpeseAnnoMensile[3]['iva'];
$spese_iva[2] = $getSpeseAnnoMensile[4]['iva']+$getSpeseAnnoMensile[5]['iva']+$getSpeseAnnoMensile[6]['iva'];
$spese_iva[3] = $getSpeseAnnoMensile[7]['iva']+$getSpeseAnnoMensile[8]['iva']+$getSpeseAnnoMensile[9]['iva'];
$spese_iva[4] = $getSpeseAnnoMensile[10]['iva']+$getSpeseAnnoMensile[11]['iva']+$getSpeseAnnoMensile[12]['iva'];

?>


<div class="row">

    	<div class="col-md-12">
		<table class="table">
			<thead>
				<tr>
					<th>Trimestre</th>
					<th>Importi</th>
</tr>
</thead>
<tbody>
	<?php for ($i=1;$i<=4;$i++):?>
	<tr>
		
		<td><?php echo $i;?>° Trimestre</td>
		<td>
			<span class="text-green">
				<?php echo e_money($fatturato[$i], '€ {number}');?> fatturato<br />
			<?php echo e_money($fatturato_iva[$i], '€ {number}');?> iva emessa
			</span>
			<br />
			<span class="text-red">
						<?php echo e_money($spese[$i], '€ {number}');?> costi<br />
						<?php echo e_money($spese_iva[$i], '€ {number}');?> iva costi
			</span>
		</td>
		
	</tr>
	<?php endfor;?>
</tbody>
</table>
</div>
</div>



<script>
    (function ($) {
	$.fn.countTo = function (options) {
		options = options || {};
		
		return $(this).each(function () {
			// set options for current element
			var settings = $.extend({}, $.fn.countTo.defaults, {
				from:            $(this).data('from'),
				to:              $(this).data('to'),
				speed:           $(this).data('speed'),
				refreshInterval: $(this).data('refresh-interval'),
				decimals:        $(this).data('decimals')
			}, options);
			
			// how many times to update the value, and how much to increment the value on each update
			var loops = Math.ceil(settings.speed / settings.refreshInterval),
				increment = (settings.to - settings.from) / loops;
			
			// references & variables that will change with each update
			var self = this,
				$self = $(this),
				loopCount = 0,
				value = settings.from,
				data = $self.data('countTo') || {};
			
			$self.data('countTo', data);
			
			// if an existing interval can be found, clear it first
			if (data.interval) {
				clearInterval(data.interval);
			}
			data.interval = setInterval(updateTimer, settings.refreshInterval);
			
			// initialize the element with the starting value
			render(value);
			
			function updateTimer() {
				value += increment;
				loopCount++;
				
				render(value);
				
				if (typeof(settings.onUpdate) == 'function') {
					settings.onUpdate.call(self, value);
				}
				
				if (loopCount >= loops) {
					// remove the interval
					$self.removeData('countTo');
					clearInterval(data.interval);
					value = settings.to;
					
					if (typeof(settings.onComplete) == 'function') {
						settings.onComplete.call(self, value);
					}
				}
			}
			
			function render(value) {
				var formattedValue = settings.formatter.call(self, value, settings);
				$self.html(formattedValue);
			}
		});
	};
	
	$.fn.countTo.defaults = {
		from: 0,               // the number the element should start at
		to: 0,                 // the number the element should end at
		speed: 10500,           // how long it should take to count between the target numbers
		refreshInterval: 30,  // how often the element should be updated
		decimals: 0,           // the number of decimal places to show
		formatter: formatter,  // handler for formatting the value before rendering
		onUpdate: null,        // callback method for every time the element is updated
		onComplete: null       // callback method for when the element finishes updating
	};
	
	function formatter(value, settings) {
		return value.toFixed(settings.decimals);
	}
}(jQuery));

jQuery(function ($) {
  // custom formatting example
  $('.count-number').data('countToOptions', {
	formatter: function (value, options) {
	  return value.toFixed(options.decimals).replace(/\B(?=(?:\d{3})+(?!\d))/g, '.');
	}
  });
  
  // start all the timers
  $('.timer').each(count);  
  
  function count(options) {
	var $this = $(this);
	options = $.extend({}, options || {}, $this.data('countToOptions') || {});
	$this.countTo(options);
  }
});
    </script>