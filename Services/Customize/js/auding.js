// auding-patch: begin
(function ($) {

	$.fn.ilAuding = function(method) {
		var internals = {
			},
			methods = {
			init: function(params) {
				return this.each(function () {
					var $this = $(this);

					if ($this.data('auding')) {
						return;
					}

					var data = {
						properties: $.extend(
							true, {}, {
								requestUrl:        "",
								accessTrackingUrl: "",
								playingEndedUrl:   "",
								mimeType:          "",
								playAllowed:       "",
								pauseAllowed:      "",
								isVideo:           "",
								txt:               {
									noRequestsLeft: ""
								}
							}, params
						),
						player: null
					};
					$this.data("auding", data);

					if (!data.properties.playAllowed) {
						$this.text(data.properties.txt.noRequestsLeft);
						return;
					}

					$this.append((function() {
						if (data.properties.isVideo) {
							return $("<video></video>")
								.css({
									"width": "100%",
									"height": "100%"
								})
								.attr({
									"width": "100%",
									"height": "100%",
									"preload":  "none",
									"controls": "controls"
								});
						} else {
							return $("<audio></audio>");
						}
					}()).attr({
						"preload":  "none",
						"controls": "controls"
					}));

					data.player = $this.find("video,audio").append(
						$("<source />").attr({
							"src":  data.properties.requestUrl,
							"type": data.properties.mimeType
						})
					).mediaelementplayer({
						videoVolume: "horizontal",
						features:         (function () {
							return ["playpause", "current", "duration", "volume", "fullscreen"];
						})(),
						clickToPlayPause: (function () {
							return data.properties.pauseAllowed;
						})(),
						success:          function (media) {
							var first_play_action  = true,
								first_click_action = true;

							if (!data.properties.pauseAllowed) {
								$this.find(".mejs-overlay-play, .mejs-playpause-button").off("click touchstart").on("click touchstart", function () {
									if (first_click_action) {
										media.play();
									}

									first_click_action = false;
								});
							}

							media.addEventListener("play", function (e) {
								if (first_play_action && data.properties.accessTrackingUrl) {
									$.ajax({
										url:      data.properties.accessTrackingUrl,
										type:     "POST",
										dataType: "json"
									});
								}

								first_play_action = false;
							}, false);

							media.addEventListener("ended", function (e) {
								if (data.properties.playingEndedUrl) {
									$.ajax({
										url:      data.properties.playingEndedUrl,
										type:     "POST",
										dataType: "json"
									}).done(function (response) {
										if (!response) {
											$this.text(data.properties.txt.noRequestsLeft);
										} else {
											first_play_action = true;
											first_click_action = true;
										}
									});
								} else {
									first_play_action = true;
									first_click_action = true;
								}
							}, false);
						}
					});

				});
			}
		};

		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === "object" || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error("Method " + method + " does not exist on jQuery.ilAuding");
		}
	};

})(jQuery);
// auding-patch: end
