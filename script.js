var iFormat = 'yyyy-MM-dd HH:mm:ss';
var oFormatDate = 'E d NNN yyyy';
var oFormatTime = 'HH:mm:ss';

$(document)
		.ready(
				function() {
					formatTimes();

					$.tablesorter.addParser({
						id : 'size',
						is : function(s) {
							return false;
						},
						format : function(s) {
							var f = parseFloat(s);
							// if (s.match(/B/i)) {
							// return f;
							// } else if (s.match(/K/i)) {
							// return f * 1024;
							// } else if (s.match(/M/i)) {
							// return f * 1024 * 1024;
							// } else if (s.match(/G/i)) {
							// return f * 1024 * 1024 * 1024;
							// } else if (s.match(/T/i)) {
							// return f * 1024 * 1024 * 1024 * 1024;
							// }
							return f;
						},
						type : 'numeric'
					});

					$.tablesorter.addParser({
						id : 'datetime',
						is : function(s) {
							return false;
						},
						format : function(s) {
							var t = Date.parseString(s, iFormat);
							if (t != null) {
								return t.getTime();
							} else {
								return 1024 * 1024 * 1024 * 1024 * 1024;
							}
						},
						type : 'numeric'
					});

					// $("#files").tablesorter({
					// widgets : [ 'zebra' ],
					// headers : {
					// 0 : {
					// sorter : 'text'
					// },
					// 1 : {
					// sorter : 'text'
					// },
					// 2 : {
					// sorter : 'size'
					// },
					// 3 : {
					// sorter : 'datetime'
					// },
					// 4 : {
					// sorter : 'numeric'
					// },
					// 5 : {
					// sorter : 'datetime'
					// },
					// 6 : {
					// sorter : false
					// }
					// }
					// });

					// $("#resultTable").tablesorter({
					// widgets : [ 'zebra' ],
					// headers : {
					// 0 : {
					// sorter : 'text'
					// },
					// 1 : {
					// sorter : 'text'
					// },
					// 2 : {
					// sorter : 'size'
					// },
					// 3 : {
					// sorter : 'datetime'
					// },
					// 4 : {
					// sorter : 'numeric'
					// },
					// 5 : {
					// sorter : 'datetime'
					// },
					// 6 : {
					// sorter : 'text'
					// },
					// 7 : {
					// sorter : false
					// }
					// }
					// });

					$("#roots").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : false
							},
							1 : {
								sorter : 'text'
							},
							2 : {
								sorter : 'text'
							},
							3 : {
								sorter : 'datetime'
							},
							4 : {
								sorter : false
							}
						},
						textExtraction : simpleTimeExtractor
					});

					$("#user").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : false
							},
							1 : {
								sorter : false
							},
							2 : {
								sorter : false
							},
							3 : {
								sorter : false
							}
						},
						textExtraction : simpleTimeExtractor
					});

					$("#users").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : 'numeric'
							},
							1 : {
								sorter : 'text'
							},
							2 : {
								sorter : 'text'
							},
							3 : {
								sorter : 'datetime'
							},
							4 : {
								sorter : 'datetime'
							},
							5 : {
								sorter : 'text'
							},
							6 : {
								sorter : false
							}
						},
						textExtraction : simpleTimeExtractor
					});

					$("#root").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : false
							},
							1 : {
								sorter : 'numeric'
							},
							2 : {
								sorter : 'text'
							},
							3 : {
								sorter : 'text'
							},
							4 : {
								sorter : 'datetime'
							}
						},
						textExtraction : simpleTimeExtractor
					});

					$("#admRoots").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : 'numeric'
							},
							1 : {
								sorter : 'text'
							},
							2 : {
								sorter : 'text'
							},
							3 : {
								sorter : 'text'
							},
							4 : {
								sorter : 'datetime'
							},
							5 : {
								sorter : 'datetime'
							},
							6 : {
								sorter : false
							},
							7 : {
								sorter : false
							}
						},
						textExtraction : simpleTimeExtractor
					});

					$("#roles").tablesorter({
						widgets : [ 'zebra' ],
						headers : {
							0 : {
								sorter : 'text'
							}
						},
						textExtraction : simpleTimeExtractor
					});

					var cache;
					$.getJSON("searchTags.php", function(data) {
						cache = data;
						$(".newTagName").autocomplete({
							minLength : 0,
							source : cache
						});
					});

					$('.newCommentForm').each(function() {
						addNewCommentFormButton($(this));
					});

					$('.newRuleForm').each(function() {
						addNewRuleFormButton($(this));
					});

					$('dd .rules').each(function() {
						addRulesListButton($(this));
					});

					$('ul.comments').each(function() {
						addCommentsListButton($(this));
					});

					$('.textFileData').each(function() {
						var id = $(this).attr('id');
						trimTextData($(this), id);
					});

					$('.fileDatas').each(function() {
						var id = $(this).attr('id');
						if ($("#" + id + " .fileData").length > 0) {
							showHideData($(this), id);
						}
					});

					$('#versions').each(function() {
						if ($('.historyVersion').length > 0) {
							showHideVersions($(this));
						}
					});

					// $("#criteriaForm")
					// .submit(
					// function(event) {
					// event.preventDefault();
					// url = $(this).attr('action');
					// $
					// .post(
					// url,
					// $(this).serialize(),
					// function(data) {
					// var content = $(data);
					// $('#messages')
					// .replaceWith(
					// content
					// .find('#messages'));
					// $(
					// "#resultTable tbody")
					// .empty()
					// .append(
					// content
					// .find('#results tbody tr'));
					// formatTimes();
					// $('#resultsHeader')
					// .replaceWith(
					// content
					// .find('#resultsHeader'));
					// $("#resultTable")
					// .trigger(
					// "update");
					//
					// });
					// });
					cleanDatepicker();

					$("#modifiedAfter, #modifiedBefore")
							.datepicker(
									{
										changeMonth : true,
										changeYear : true,
										numberOfMonths : 3,
										showButtonPanel : true,
										dateFormat : 'yy-mm-dd',
										onSelect : function(selectedDate) {
											var option = this.id == "modifiedAfter" ? "minDate"
													: "maxDate", instance = $(
													this).data("datepicker"), date = $.datepicker
													.parseDate(
															instance.settings.dateFormat
																	|| $.datepicker._defaults.dateFormat,
															selectedDate,
															instance.settings);
											dates.not(this).datepicker(
													"option", option, date);
										}
									});
					$("#firstMentionAfter, #firstMentionBefore")
							.datepicker(
									{
										changeMonth : true,
										changeYear : true,
										numberOfMonths : 3,
										showButtonPanel : true,
										dateFormat : 'yy-mm-dd',
										onSelect : function(selectedDate) {
											var option = this.id == "firstMentionAfter" ? "minDate"
													: "maxDate", instance = $(
													this).data("datepicker"), date = $.datepicker
													.parseDate(
															instance.settings.dateFormat
																	|| $.datepicker._defaults.dateFormat,
															selectedDate,
															instance.settings);
											dates.not(this).datepicker(
													"option", option, date);
										}
									});
					$('#paginationSettingsSubmit').hide();
					$('#paginationPerPageSelect').change(function() {
						$('#paginationSettingsForm').submit();
					});
				});

function simpleTimeExtractor(node) {
	text = '';
	children = $(node).contents();
	for ( var i = 0; i < children.length; i++) {
		child = children[i];
		switch (child.nodeType) {
		case 3: // text
			text += $.trim($(child).text());
			break;
		case 1: // element
			el = $(child);
			if (el.is('.time') || el.is('.timebr')) {
				text += $.trim(el.attr('data-time'));
			} else if (el.is('.filesize')) {
				text += $.trim(el.attr('data-filesize'));
			} else {
				text += $.trim($(el).text());
			}
			break;
		}
	}
	return text;
}

function formatTimes() {
	$(".timebr").each(
			function() {
				var time1 = Date
						.parseString($(this).attr("data-time"), iFormat);
				$(this).html(time1.format(oFormatDate));
				$(this).attr(
						'title',
						time1.format(oFormatDate) + ' '
								+ time1.format(oFormatTime));
			});
	$(".time").each(
			function() {
				var time2 = Date
						.parseString($(this).attr("data-time"), iFormat);
				$(this).html(
						time2.format(oFormatDate) + ' '
								+ time2.format(oFormatTime));
			});
}

function cleanDatepicker() {
	var old_fn = $.datepicker._updateDatepicker;

	$.datepicker._updateDatepicker = function(inst) {
		old_fn.call(this, inst);

		var buttonPane = $(this).datepicker("widget").find(
				".ui-datepicker-buttonpane");

		$(
				"<button type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>Clear</button>")
				.appendTo(buttonPane).click(function(ev) {
					$.datepicker._clearDate(inst.input);
				});
	};
}

function addNewCommentFormButton(form) {
	form.hide();
	form.before("<button onclick=\"$(this).remove(); $('#" + form.attr('id')
			+ "').show();\">Add comment</button>");
}

function addNewRuleFormButton(form) {
	form.hide();
	form.before("<button onclick=\"$(this).remove(); $('#" + form.attr('id')
			+ "').show();\">Add rule</button>");
}

function addCommentsListButton(ul) {
	if (ul.find('.noComments').length == 0) {
		ul.prepend("<li><button onclick=\"$(this).parent().remove(); $('#"
				+ ul.attr('id') + " li').show();\">Show comments ("
				+ ($('#' + ul.attr('id') + ' li').length - 1)
				+ ")</button></li>");
		ul.find('li:not(:first)').hide();
	}
}

function addRulesListButton(ul) {
	if (ul.find('.noRules').length == 0) {
		ul.prepend("<li><button onclick=\"$(this).parent().remove(); $('#"
				+ ul.attr('id') + " li').show();\">Show rules ("
				+ ($('#' + ul.attr('id') + ' li').length - 1)
				+ ")</button></li>");
		ul.find('li:not(:first)').hide();
	}
}

function showHideVersions(versions) {
	versions
			.prepend("<li class='version'><button onclick=\"$('.historyVersion').toggle();\">"
					+ "Show/Hide history versions ("
					+ $('.historyVersion').length + ")</button></li>");
	$('.historyVersion').hide();
}

function showHideData(fileDatas, id) {
	fileDatas.prepend("<li class='fileData'><button onclick=\"$('" + '#' + id
			+ ' .fileData:not(:first)' + "').toggle();\">"
			+ "Show/Hide file data (" + $('#' + id + ' .fileData').length
			+ ")</button></li>");
	$('#' + id + " .fileData:not(:first)").hide();
}

function trimTextData(fileData, id) {
	$('#' + id + ' .fileDataHeader').append(
			"<button onclick=\"$('" + '#' + id + ' .fileDataBody'
					+ "').toggleClass('trimmed');\">"
					+ "Show full/trimmed text data</button>");
	$('#' + id + ' .fileDataBody').toggleClass('trimmed');
}