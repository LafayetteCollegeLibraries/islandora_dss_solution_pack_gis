/**
 * @file JavaScript integration of the TimeMap widget
 * @author griffinj@lafayette.edu
 *
 */

var Islandora = window.document.Islandora || {};
Islandora.GIS = Islandora.GIS || {};

/**
 * TimelineController Class
 *
 */
TimelineController = function($, timeline, forwardElement, backwardElement) {

    this.timeline = timeline;

    // Moving a time step forwards
    // This moves the Timeline forwards a step, hides certain layers, and renders others
    $(forwardElement).click(function(event) {
	    
	    // Should there be an animation for handling the transition exposed by OpenLayers?

	    // Update OpenLayers
	});

    // Moving a time step backwards
    // This moves the Timeline backwards a step, hides certain layers, and renders others
    $(backwardElement).click(function(event) {

	    // Should there be an animation for handling the transition exposed by OpenLayers?
	});
}

/**
 * TimelineExhibit class
 *
 */
TimelineExhibit = function(element, map, objects, eventSourceData, beginDate) {

    //this.$ = $;
    this.element = element;
    this.map = map;
    
    this.objects = objects;

    this.eventSource = new Timeline.DefaultEventSource();

    // Initialize the Timeline widget
    /*
    this.bandInfos = [
		      Timeline.createBandInfo({

			      eventSource: this.eventSource,
			      width:          "30%",
			      intervalUnit:   Timeline.DateTime.YEAR,
			      intervalPixels: 100,
			      date: beginDate
			  }),
		      Timeline.createBandInfo({

			      eventSource: this.eventSource,
			      width:          "70%",
			      intervalUnit:   Timeline.DateTime.MONTH,
			      intervalPixels: 100,
			      date: beginDate
			  })
		      ];
    */
    this.bandInfos = [
		      Timeline.createBandInfo({

			      eventSource: this.eventSource,
			      width:          "100%",
			      intervalUnit:   Timeline.DateTime.YEAR,
			      intervalPixels: 100,
			      date: beginDate
			  })
		      ];

    // Create the timeline
    this.timeline = Timeline.create(this.element, this.bandInfos);

    // Ensure that the timeline can reference this Object
    this.timeline.exhibit = this;

    // Retrieve the data for the events
    this.eventSource.loadJSON(eventSourceData, '/islandora_gis_timeline/event_source');
};

/**
 * Static Methods
 *
 */
TimelineExhibit.mouseWheelHandlers = [];

TimelineExhibit.mouseWheelHandler = function() {

    var handler = function(evt) {

	evt = (evt) ? evt : ((event) ? event : null);
        if (evt) {
            var target = (evt.target) ?
                evt.target : ((evt.srcElement) ? evt.srcElement : null);
            if (target) {
                target = (target.nodeType == 1 || target.nodeType == 9) ?
                    target : target.parentNode;
            }

            return handler(elmt, evt, target);
        }

        //return true;
	return TimelineExhibit.mouseWheelHandlers.map( function(afterCallback) {

		afterCallback.call(this, evt)
	    } ).reduce(function(u,v) { u || v });
    };

    return handler;
};

/**
 * Iterate through each Openlayers layer, and try to determine whether or not certain layers should be rendered
 * This is, unfortunately, a limitation imposed by the structure of the data set
 * Instead, WMS-T should be leveraged as the means by which to filter upon data sets within certain decades
 *
 */
TimelineExhibit.prototype.updateOpenlayers = function() {
    
    var minDate = this.timeline.getBand(0).getMinVisibleDate();
    var maxDate = this.timeline.getBand(0).getMaxVisibleDate();

    // Work-around
    // This handles cases in which the Timeline is hidden
    if(minDate.getTime() == maxDate.getTime()) {

	maxDate = new Date(minDate.getTime() + 315619200000);
    }

    for(var pid in this.objects) {

	var objectMinDate = new Date(this.objects[pid].start);
	var objectMaxDate = new Date(this.objects[pid].end);


	//var layer = this.map.layers[pid];
	var layers = this.map.getLayersByName(pid);

	if(layers.length > 0) {

	    var layer = layers[0];

	    if(objectMinDate < minDate || objectMaxDate > maxDate) {

		// Hide the related OpenLayers layer
		layer.setVisibility(false);
	    } else {

		// If the layer has been hidden, render it
		layer.setVisibility(true);
	    }
	}
    }
};

/**
 * Integration with the Drupal API
 *
 */
(function($, Drupal, Islandora) {

    Drupal.behaviors.islandoraGisTimeline = {

	attach: function (context, settings) {

	    // Work-around
	    // @todo Identify precisely why this is being invoked more than once
	    if($('#' + settings.islandoraGisTimeline.elementSelector).length == 0) {

		var startDate = new Date(settings.islandoraGisTimeline.startDate);
		var endDate = new Date(settings.islandoraGisTimeline.endDate);

		// Append the element
		$('#openlayers-container-openlayers-map').after('<div id="' + settings.islandoraGisTimeline.elementSelector + '"></div>');

		if(settings.islandoraGisTimeline.visibility == false) {
		    
		    $('#' + settings.islandoraGisTimeline.elementSelector).hide();
		}

		$('#' + settings.islandoraGisTimeline.elementSelector)
		.after('<button id="timeline-step-forward"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>Step Forward</button>')
		.after('<div id="timeline-slider"></div>')
		.after('<button id="timeline-step-backward"><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>Step Backward</button>');

		/*
		$('#timeline-slider').slider({

			value: startDate.getTime() / settings.islandoraGisTimeline.timeStep,
			min: startDate.getTime() / settings.islandoraGisTimeline.timeStep,
			max: endDate.getTime() / settings.islandoraGisTimeline.timeStep,
			step: (endDate.getTime() - startDate.getTime()) / settings.islandoraGisTimeline.timeStep,
			slide: function( event, ui ) {

			    //$( "#amount" ).val( "$" + ui.value );
			    var band = timelineExhibit.timeline.getBand(0);
			    var currentDate = band.getCenterVisibleDate();
			    var nextDate = new Date(ui.value);

			    band.setCenterVisibleDate( nextDate );
			}
		    });
		*/
		$('#timeline-slider').slider({

			value: 0,
			min: 0,
			max: 10,
			step: 1,
			slide: function( event, ui ) {

			    //$( "#amount" ).val( "$" + ui.value );
			    var band = timelineExhibit.timeline.getBand(0);
			    var currentDate = band.getCenterVisibleDate();
			    var nextDate = new Date( startDate.getTime() + (ui.value * settings.islandoraGisTimeline.timeStep) );

			    band.setCenterVisibleDate( nextDate );
			}
		    });

		//$('.islandora-gis-object').after('<div id="' + settings.islandoraGisTimeline.elementSelector + '"></div>');

		var element = document.getElementById(settings.islandoraGisTimeline.elementSelector);
		var map = $('.openlayers-map').data('openlayers').openlayers;

		var timelineExhibit = new TimelineExhibit(element, map, settings.islandoraGisTimeline.objects, settings.islandoraGisTimeline.eventSourceData, settings.islandoraGisTimeline.startDate);
		timelineExhibit.timeline.getBand(0).addOnScrollListener(function(band) {

			band.getTimeline().exhibit.updateOpenlayers();
		    });

		// Provide the controller functionality
		$('#timeline-step-forward').click(function(e) {

			var band = timelineExhibit.timeline.getBand(0);
			var currentDate = band.getCenterVisibleDate();
			var nextDate = new Date(currentDate.getTime() + settings.islandoraGisTimeline.timeStep);

			band.setCenterVisibleDate( nextDate );

			$('#timeline-slider').slider('value', 10);
		    });
		$('#timeline-step-backward').click(function(e) {

			var band = timelineExhibit.timeline.getBand(0);
			var currentDate = band.getCenterVisibleDate();
			var nextDate = new Date(currentDate.getTime() - settings.islandoraGisTimeline.timeStep);

			band.setCenterVisibleDate( nextDate );

			$('#timeline-slider').slider('value', 10);
		    });
	    }
	}
    };
}(jQuery, Drupal, Islandora));
