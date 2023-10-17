<?php

$this->load->model('contabilita/conteggi');

$anno = (!empty($this->input->get('anno'))) ? $this->input->get('anno') : date('Y');
$getFatturato = $this->conteggi->getFatturatoAnno($anno);
$fatturato = number_format($getFatturato['imponibile'], 0, "", "");

$getCreditiClienti = $this->conteggi->getCreditiClientiAnno($anno);
$crediti = number_format($getCreditiClienti, 0, "", "");

$getDebitiFornitori = $this->conteggi->getDebitiFornitoriAnno($anno);
$debiti = number_format($getDebitiFornitori, 0, "", "");

$getSpeseAnno = $this->conteggi->getSpeseAnno($anno);
$spese = number_format($getSpeseAnno['imponibile'], 0, "", "");

$getCostiDeducibili = $this->conteggi->getCostiDeducibiliAnno($anno);
$utile = number_format($getFatturato['imponibile'] - $getCostiDeducibili['costi_deducibili'], 0, "", "");

?>

<style>
	.filtri_centrali .badge {
		font-size: 30px
	}

	.gren.light .badge {
		height: 30px;
		width: 100px;
		font-size: 2em !important;
	}

	.light .portlet-body {
		padding-top: 0px !important;
	}

	.row_counters {
		background-color: #e3e3e3;
	}

	.counter {
		background-color: #ffffff;
		color: #0A90D4;
		padding: 20px 0;
		border-radius: 5px;
		text-align: center;
		margin: 20px;
	}

	.count-title {
		font-size: 40px;
		font-weight: normal;
		margin-top: 10px;
		margin-bottom: 0;
		text-align: center;
		display: inline;
	}

	.count-text {
		font-size: 16px;
		font-weight: normal;
		margin-top: 10px;
		margin-bottom: 0;
		text-align: center;
	}

	.count-green {
		color: #3DA50C;

	}

	.count-red {
		color: #BD0000;
	}
</style>

<div class="filtri_centrali" style="margin:0 auto;width:390px">
	<?php

	$filtro_fatture = (array) @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];
	$field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'documenti_contabilita_data_emissione'")->row()->fields_id;
	$anno_corrente = date('Y');
	//Aggiungo i pulsanti
	for ($i = $anno_corrente - 3; $i <= $anno_corrente; $i++):
		$color = ($i == $anno) ? 'red' : 'green';
		?>
		<span class=" badge bg-<?php echo $color; ?>"><a style="color:#FFF;"
				href="<?php echo base_url('main/layout/dashboard-amministrativa?anno=' . $i); ?>"><?php echo $i; ?></a></span>
	<?php endfor; ?>
</div>


<div class="row row_counters">
	<div class="counter col-md-2">
		<h2 class="count-title">€ </h2>
		<h2 class="timer count-title count-number" data-to="<?php echo $fatturato; ?>" data-speed="1500"></h2>
		<p class="count-text ">Fatturato</p>
	</div>

	<div class="counter  col-md-2">
		<h2 class="count-title">€ </h2>
		<h2 class="timer count-title count-number" data-to="<?php echo $spese; ?>" data-speed="1500"></h2>
		<p class="count-text ">Costi</p>
	</div>

	<div class="counter  col-md-2">
		<h2 class="count-title">€ </h2>
		<h2 class="timer count-title count-number" data-to="<?php echo $utile; ?>" data-speed="1500"></h2>
		<p class="count-text ">Utile</p>
	</div>

	<div class="counter  col-md-2 count-green">
		<h2 class="count-title ">€ </h2>
		<h2 class="timer count-title count-number " data-to="<?php echo $crediti; ?>" data-speed="1500"></h2>
		<p class="count-text ">Crediti aperti</p>
	</div>

	<div class="counter  col-md-2 count-red">
		<h2 class="count-title ">€ </h2>
		<h2 class="timer count-title count-number " data-to="<?php echo $debiti; ?>" data-speed="1500"></h2>
		<p class="count-text ">Debiti aperti</p>
	</div>
</div>



<script>
	(function ($) {
		$.fn.countTo = function (options) {
			options = options || {};

			return $(this).each(function () {
				// set options for current element
				var settings = $.extend({}, $.fn.countTo.defaults, {
					from: $(this).data('from'),
					to: $(this).data('to'),
					speed: $(this).data('speed'),
					refreshInterval: $(this).data('refresh-interval'),
					decimals: $(this).data('decimals')
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

					if (typeof (settings.onUpdate) == 'function') {
						settings.onUpdate.call(self, value);
					}

					if (loopCount >= loops) {
						// remove the interval
						$self.removeData('countTo');
						clearInterval(data.interval);
						value = settings.to;

						if (typeof (settings.onComplete) == 'function') {
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