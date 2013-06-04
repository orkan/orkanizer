function _init1() {
	//alert("init: jQuery ready!");
	$(document).bind("mobileinit", function(){

		$( document ).bind( "pagecreate create", function( e ){
			// Collapsible: exchange default CueText with ui-collapsible-content text() when collapsed
			$( $.mobile.collapsible.prototype.options.initSelector, e.target ).each(function(i){
				var $el = $(this), o = $(this).jqmData("collapsible").options;
				o.expandCueText = $el.is(":jqmData(desc='false')") ? "" : $el.find(".ui-collapsible-content").eq(0).text().substring(0, o.cueLen);
				$el.find(".ui-collapsible-heading-status").text( $el.find(".ui-collapsible-heading-collapsed").length ? o.expandCueText : o.collapseCueText );
			});
		});

	});
};
function _init2() {
	//_init2("init: jQuery Mobile ready!");

	$.mobile.loadingMessage = "Ładuję...";
	$.mobile.pageLoadErrorMessage = "Błąd podczas ładowania strony!";

	var $loader = $(".ui-loader");
	var isMobile = typeof orientation != "undefined" ? true : false;
	var autoCollapse = !$.mobile.media("screen and (min-width: 768px)");

	$.mobile.page.prototype.options.theme = "a";
	$.extend($.mobile.listview.prototype.options, {
		theme: "a",
		filterTheme: "a",
		countTheme: "c"
	});
	$.extend($.mobile.collapsible.prototype.options, {
		iconTheme: "a",
		collapsed: autoCollapse,
		expandCueText: "",
		collapseCueText: "",
		cueLen: 400
	});

	// Opóźnij ładowanie stron w iPhonie, żeby ekran nie świecił na biało w czasie parsowania
	var _changePage = $.mobile.changePage;
	$.mobile.changePage = function( toPage, options ) {
		setTimeout(function(){
			_changePage.call( $(this), toPage, options );
		},100);
	}

/*
	// dla loadPage
	var _registerInternalEvents = $.mobile._registerInternalEvents;
	$.mobile._registerInternalEvents = function(){
console.log("_registerInternalEvents");
		setTimeout(function(){
			_registerInternalEvents.call( $(this) );
		},100);
	};

	var _loadPage = $.mobile.loadPage;
	$.mobile.loadPage = function( url, options ) {
		setTimeout(function(){
			return _loadPage.call( $(this), url, options ); // return? hmmm...
		},100);
	}
*/

	$(function(){
		//alert("page load ready!");

		/* a niech bedzie ostatni :p
		setTimeout(function(){ // open the first element of "collapsible-set" - instead of last (def.)
			$(":jqmData(role='collapsible-set')").find(":jqmData(role='collapsible'):eq(0)").trigger("expand");
		},100);
		*/

		$("a:jqmData(rel='top')").click(function(){
			$.mobile.silentScroll();
		});
		$("a.hidden_email").each(function(){
			var re = /ukryty/gi, to = "orkans";
			$(this).attr("href", $(this).attr("href").replace(re,to));
			$(this).text($(this).text().replace(re,to));
		});
	});
};
document.write('<script src="sys/jquerymobile/jquery.min.js"></script>');
document.write("<script>_init1();</script>");
document.write('<script src="sys/jquerymobile/jquery.mobile.min.js"></script>');
document.write("<script>_init2();</script>");

// ========================
// Functions, etc...
// ========================
orkan = {};
String.prototype.reverse=function(){
	return this.split("").reverse().join("");
};
function seachInputFind(id, s) {
	var $list = $("#"+ id +"_list").html(""),
		type = id.split("_")[1],
		found = [],
		out = "";
	$.mobile.showPageLoadingMsg();
	setTimeout(function(){

	$.each(orkan.pois, function(key,val){
		switch(type) {
			case "id":
			if(s.length == 0) return seachInputClose(id);
			if(key.indexOf(s) === 0) found.push(key);
			break;
			case "name":
			if(s.length < 2) return seachInputClose(id);
			var s1 = val.txt.toLowerCase(), s2 = s.toLowerCase();
			if(s1.indexOf(s2) > -1) found.push(key);
			break;
		}
	});
	$.each(found, function(){
		out += '<li><a href="'+ orkan.subdir +
				orkan.pois[this].url +'" data-ajax="false"><img class="ui-li-icon flag flag-'+
				orkan.pois[this].ccISO +'" alt="'+
				orkan.pois[this].ccONZ +'" src="sys/flags/blank.gif">'+
				orkan.pois[this].txt +'</a><span class="ui-li-count">'+
				orkan.pois[this].com +'</span></li>';
	});
	if(found.length) {
		$list.html('<ul>'+ out +'</ul>');
		$list.children(0).listview();
	}

	$.mobile.hidePageLoadingMsg();
	},100);
};
function seachInputClose(id) {
	$("#"+ id +"_list").html("");
	return true;
};
