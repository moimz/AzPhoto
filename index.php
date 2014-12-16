<?php
REQUIRE_ONCE './configs/default.conf.php';
$db = new db();
$lastPhoto = $db->getRow($_ENV['tables']['photos'],"order by `time` desc limit 0,1");
if (isset($lastPhoto['filename']) == false) {
	$lastPhoto['longitude'] = 233;
	$lastPhoto['latitude'] = 128;
	$lastPhoto['time'] = time();
}
?>
<!DOCTYPE HTML>
<html lang="ko">
<head>
	<meta http-equiv="Content-Type" content="text/html" charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<title>AzPhoto Beta <?php echo $_ENV['version']; ?></title>
	<link rel="stylesheet" href="./styles/style.css">
	<link rel="stylesheet" href="./styles/font.awesome.min.css">
	<script src="./scripts/jquery-1.11.1.min.js"></script>
	<script src="./scripts/jquery-ui-1.11.2.min.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=<?php echo $_ENV['apikey']; ?>&sensor=TRUE"></script>
	<script>
	var gMap = null;
	var gCalendar = null;
	var gViewerMap = null;
	var gViewerMapPin = null;
	var gPins = [];
	var gImageList = null;
	var gPosition = null;
	var gResizeTimeout = null;
	var gPinLoadTimeout = null;
	var gCalendarLoadTimeout = null;
	</script>
</head>
<body>
	<div id="Header">
		<div class="map">
			<img src="./images/logo.png">
		</div>
		
		<div class="calendar">
			<table cellpadding="0" cellspacing="0" class="layoutfixed">
			<col width="100"><col width="100%"><col width="100">
			<tr>
				<td class="month"></td>
				<td class="center"><img src="./images/logo.png"></td>
				<td class="arrow">
					<div>
						<span onclick="showCalendar('prev');"></span>
						<span onclick="showCalendar('next');"></span>
					</div>
				</td>
			</tr>
			</table>
		</div>
		
		<div class="viewer">
			<div class="help">키보드 방향키로 사진 앞/뒤 이동이 가능합니다</div>
			<div class="text"></div>
		</div>
	</div>
	
	<div id="Map"></div>
	
	<div id="Calendar">
		<div class="loader"></div>
		<div class="calendar"></div>
	</div>
	
	<div id="Viewer">
		<div class="imageView"></div>
		<div class="infoView">
			<div class="exif">
				<div class="default"></div>
				<div class="setting">
					<div class="iso"></div>
					<div class="focus"></div>
					<div class="ev"></div>
					<div class="lens"></div>
					<div class="opentime"></div>
				</div>
			</div>
			
			<div class="title"></div>
			<div class="time"></div>
			<div class="description"></div>
			
			<div id="ViewerMap" class="map"></div>
		</div>
	</div>
	
	<div id="Footer">
		<div class="map">
			<table cellpadding="0" cellspacing="0" class="layoutfixed">
			<col width="100"><col width="20"><col width="110"><col width="20"><col width="50"><col width="100%"><col width="300">
			<tr>
				<td></td>
				<td class="zoomArea"><div class="zoomout" onclick="$('#Footer > .map .slider').slider('value',$('#Footer > .map .slider').slider('value')-1);"></div></td>
				<td class="sliderArea">
					<div class="slider"></div>
					
					<script> 
					$("#Footer > .map .slider").slider({
						animate:true,
						range:"min",
						value:4,
						min:2,
						max:17,
						step:1,
						change:function(event,ui) {
							if (ui.value != gMap.getZoom()) gMap.setZoom(ui.value);
						}
					});
					</script>
					
					<div class="text">확대/축소</div>
				</td>
				<td class="zoomArea"><div class="zoomin" onclick="$('#Footer > .map .slider').slider('value',$('#Footer > .map .slider').slider('value')+1);"></div></td>
				<td></td>
				<td class="help">
					지도를 확대/축소하면 확대비율에 맞게 핀이 통합/분리 됩니다.<br />지도위의 핀을 클릭하면 해당 지역의 사진을 모아서 볼 수 있습니다.
				</td>
				<td>
					<div class="viewmode">
						<div class="on">
							<span class="fa fa-globe"></span>
							지도보기
						</div>
						
						<div onclick="showCalendar(<?php echo date('Y',$lastPhoto['time']); ?>,<?php echo date('n',$lastPhoto['time']); ?>);">
							<span class="fa fa-calendar"></span>
							달력보기
						</div>
					</div>
				</td>
			</tr>
			</table>
		</div>
		
		<div class="calendar">
			<table cellpadding="0" cellspacing="0" class="layoutfixed">
			<col width="300"><col width="100%"><col width="300">
			<tr>
				<td></td>
				<td class="help">
					사진이 보이는 날짜칸에서 마우스를 좌우로 움직이면 해당날짜의 사진을 넘겨볼 수 있습니다.<br>
					또는 해당날짜칸을 마우스클릭하여 해당날짜의 모든 사진을 사진뷰어창으로 확인할 수 있습니다.
				</td>
				<td>
					<div class="viewmode">
						<div onclick="showMap();">
							<span class="fa fa-globe"></span>
							지도보기
						</div>
						
						<div class="on">
							<span class="fa fa-calendar"></span>
							달력보기
						</div>
					</div>
				</td>
			</tr>
			</table>
		</div>
		
		<div class="viewer">
			<table cellpadding="0" cellspacing="0" class="layoutfixed">
			<col width="300"><col width="100%"><col width="300">
			<tr>
				<td></td>
				<td>
					<div class="thumbnail">
						<div class="left"></div>
						<div class="center"></div>
						<div class="right"></div>
					</div>
				</td>
				<td></td>
			</tr>
			</table>
		</div>
	</div>

	<script>
	function getFileSize(filesize,length) {
		if(filesize < 1024) {
			return filesize+"B";
		} else if(filesize < 1048576) {
			return (filesize/1024).toFixed(length)+"KB";
		} else if (filesize < 1073741824) {
			return (filesize/1048576).toFixed(length)+'MB';
		} else {
			return (filesize/1073741824).toFixed(length)+"GB";
		}
	}
	
	function showMap() {
		$("#Viewer").hide();
		$("#Calendar").hide();
		
		$("#NaviArrow").remove();
		$("#Header > .viewer").hide();
		$("#Header > .calendar").hide();
		$("#Header > .map").show();
		
		$("#Footer > .viewer").hide();
		$("#Footer > .calendar").hide();
		$("#Footer > .map").show();
	}
	
	function showCalendar(year,month) {
		$("#Viewer").hide();
		$("#Calendar").show();
		
		$("#NaviArrow").remove();
		$("#Header > .viewer").hide();
		$("#Header > .map").hide();
		$("#Header > .calendar").show();
		
		$("#Footer > .viewer").hide();
		$("#Footer > .map").hide();
		$("#Footer > .calendar").show();
		
		$("#Calendar").height($(window).height() - $("#Header").height() - $("#Footer").height());
		$("#Calendar .loader").width($("#Calendar").width()).height($("#Calendar").height()).show();
		
		if (year == "prev") {
			year = gCalendar.year;
			month = gCalendar.month - 1;
		} else if (year == "next") {
			year = gCalendar.year;
			month = gCalendar.month + 1;
		}
		
		var time = new Date(year,month-1,1);
		gCalendar = {year:time.getFullYear(),month:time.getMonth()+1};
		var lastDate = new Date(year,month,0).getDate();
		
		$("#Header .calendar .month").html(gCalendar.year+"."+gCalendar.month);
		
		var date = time.getDay() * -1 + 1;
		var isStart = false;
		var calendar = $("<table>");
		var title = $("<tr>").addClass("title");
		title.append($("<td>").addClass("sunday").append($("<div>").html("일")));
		title.append($("<td>").addClass("weekday").append($("<div>").html("월")));
		title.append($("<td>").addClass("weekday").append($("<div>").html("화")));
		title.append($("<td>").addClass("weekday").append($("<div>").html("수")));
		title.append($("<td>").addClass("weekday").append($("<div>").html("목")));
		title.append($("<td>").addClass("weekday").append($("<div>").html("금")));
		title.append($("<td>").addClass("saturday").append($("<div>").html("토")));
		calendar.append(title);
		
		for (var i=0, loop=42;i<loop;i++) {
			if (i % 7 == 0) var week = $("<tr>").addClass("days");
			if (i == 0) {
				var startDay = new Date(year,month-1,date).getTime();
				week.addClass("firstWeek");
			}
			
			var day = $("<td>").attr("date",new Date(year,month-1,date).getFullYear()+"-"+(new Date(year,month-1,date).getMonth()+1 < 10 ? "0"+(new Date(year,month-1,date).getMonth()+1) : new Date(year,month-1,date).getMonth()+1)+"-"+(new Date(year,month-1,date).getDate() < 10 ? "0"+new Date(year,month-1,date).getDate() : new Date(year,month-1,date).getDate())).data("photos",[]);
			
			day.on("mouseout",function() {
				if ($(this).data("photos").length == 0 || $(this).data("photos").length == 1) return;
				$(this).find(".photo").prop("scrollTop",0);
			});
			
			day.on("mousemove",function() {
				if ($(this).data("photos").length == 0 || $(this).data("photos").length == 1) return;
				
				var page = Math.round(event.offsetX / ($(this).width() / ($(this).data("photos").length - 1)));
				var photo = $(this).find(".photo");
				
				console.log(page,event.offsetX);
				
				photo.prop("scrollTop",page*photo.height());
			});
			
			day.on("click",function() {
				if ($(this).data("photos").length == 0) return;
				showViewer("calendar",$(this).data("photos"));
			});
			
			var area = $("<div>").addClass("area");
			
			if (isStart == false && i == time.getDay()) isStart = true;
			
			if (i % 7 == 0) day.addClass("sunday");
			else if (i % 7 == 6) day.addClass("saturday");
			else day.addClass("weekday");
			
			area.append($("<div>").addClass("date").html(new Date(year,month-1,date).getDate()));
			if (isStart == false) {
				day.addClass("notThisMonth");
			} else if (isStart == true && date <= lastDate) {
				
			} else {
				day.addClass("notThisMonth");
			}
			
			var photo = $("<div>").addClass("photo");
			area.append(photo);
			
			day.append(area);
			week.append(day);
			
			var endDay = new Date(year,month-1,date).getTime();
			
			date++;
			
			if (i % 7 == 6) {
				calendar.append(week);
				if (date >= lastDate) {
					week.addClass("lastWeek");
					break;
				}
			}
		}
		
		$("#Calendar > .calendar").empty();
		$("#Calendar > .calendar").append(calendar);
		
		var dayHeight = Math.floor(($("#Calendar").height()-40)/$("#Calendar").find("tr.days").length);
		for (var i=0, loop=$("#Calendar").find("tr.days").length;i<loop;i++) {
			if (i + 1 == loop) $($("#Calendar").find("tr.days")[i]).find("div.area").outerHeight($("#Calendar").height()-40-dayHeight*(loop-1));
			else $($("#Calendar").find("tr.days")[i]).find("div.area").outerHeight(dayHeight);
		}
		
		for (var i=0, loop=$("#Calendar").find("div.photo").length;i<loop;i++) {
			var photo = $($("#Calendar").find("div.photo")[i]);
			photo.width(photo.parent().width());
			photo.height(photo.parent().height());
		}
		
		if (gCalendarLoadTimeout != null) {
			clearTimeout(gCalendarLoadTimeout);
			gCalendarLoadTimeout = null;
		}
		gCalendarLoadTimeout = setTimeout(loadCalendar,500,startDay,endDay);
	}
	
	function showViewer(mode,photos) {
		gImageList = photos;
		
		$("body").append($("<div>").attr("id","NaviArrow").append($("<div>").addClass("left")).append($("<div>").addClass("center")).append($("<div>").addClass("right")));
		
		if (mode == "map") {
			$("#NaviArrow > .center").html("지도로 돌아가기");
			$("#NaviArrow").on("click",function() {
				showMap();
			});
		} else {
			$("#NaviArrow > .center").html("달력으로 돌아가기");
			$("#NaviArrow").on("click",function() {
				showCalendar(gCalendar.year,gCalendar.month);
			});
		}
		
		$("#Viewer").show();
		$("#Viewer").height($(window).height() - $("#Header").height() - $("#Footer").height());
		$("#Viewer > .imageView").width($("#Viewer").width() - 300);
		$("#Viewer > .imageView").height($("#Viewer").height());
		$("#Viewer > .infoView").height($("#Viewer").height());
		
		$("#Header > .map").hide();
		$("#Header > .calendar").hide();
		$("#Header > .viewer").show();
		
		$("#Footer > .map").hide();
		$("#Footer > .calendar").hide();
		$("#Footer > .viewer").show();
		$("#Footer > .viewer .thumbnail > .center").width($("#Footer > .viewer .thumbnail").width() - 10);
		
		$("#Footer > .viewer .thumbnail > .center").empty();
		for (var i=0, loop=photos.length;i<loop;i++) {
			var thumbnail = $("<div>").addClass("item").width(Math.ceil(34*photos[i].width/photos[i].height)).height(36);
			thumbnail.append($("<img>").attr("src","./userfiles/thumbnail/"+photos[i].filename+".jpg").width(Math.ceil(34*photos[i].width/photos[i].height)).height(34));
			thumbnail.data("position",i);
			thumbnail.on("click",function() {
				showImage($(this).data("position"));
			});
			$("#Footer > .viewer .thumbnail > .center").append(thumbnail);
		}
		
		if (photos.length > 0) showImage(0);
	}
	
	function showImage(position) {
		$("#Header > .viewer > .text").html((position+1)+" / "+gImageList.length);
		$("#Viewer > .imageView").empty();
		$("#Viewer > .imageView").append($("<div>").addClass("loader").width($("#Viewer > .imageView").width()).height($("#Viewer > .imageView").height()));
		
		gPosition = position;
		var photo = gImageList[gPosition];
		
		var thumbnail = $($("#Footer > .viewer .thumbnail > .center > div.item").get(position));
		$("#Footer > .viewer .thumbnail > .center").find("div.selected").remove();
		
		var selected = $("<div>").addClass("selected");
		selected.append($("<div>").addClass("left"));
		selected.append($("<div>").addClass("center").width(thumbnail.width()-10));
		selected.append($("<div>").addClass("right"));
		thumbnail.append(selected);
		
		var positionLeft = thumbnail.position().left - $("#Footer > .viewer .thumbnail > .center").position().left + $("#Footer > .viewer .thumbnail > .center").prop("scrollLeft");
		var scrollLeft = positionLeft - $("#Footer > .viewer .thumbnail > .center").width() / 2 + thumbnail.width() / 2 < 0 ? 0 : positionLeft - $("#Footer > .viewer .thumbnail > .center").width() / 2 + thumbnail.width() / 2;
		$("#Footer > .viewer .thumbnail > .center").animate({scrollLeft:scrollLeft},"fast");
		
		var image = $("<img>").attr("src","./userfiles/viewer/"+photo.filename+".jpg");
		image.on("load",function() {
			$("#Viewer > .imageView > .loader").remove();
		});
		if ($("#Viewer > .imageView").width() * photo.height < $("#Viewer > .imageView").height() * photo.width) {
			var width = $("#Viewer > .imageView").width();
			var height = width * photo.height / photo.width;
			image.css("marginTop",($("#Viewer > .imageView").height()-height)/2);
		} else {
			var height = $("#Viewer > .imageView").height();
			var width = height * photo.width / photo.height;
		}
		image.css("width",width).css("height",height);
		$("#Viewer > .imageView").append(image);
		
		$("#Viewer > .infoView > .exif > .default").html(photo.exif.Model+"<br>"+(photo.exif["UndefinedTag:0xA434"] ? photo.exif["UndefinedTag:0xA434"] : photo.exif.Make)+"<br>"+photo.width+" x "+photo.height+"&nbsp;&nbsp;"+getFileSize(photo.exif.FileSize));
		$("#Viewer > .infoView > .exif > .setting > .iso").html("ISO "+photo.exif.ISOSpeedRatings);
		$("#Viewer > .infoView > .exif > .setting > .focus").html((parseInt(photo.exif.FocalLength.split("/").shift())/parseInt(photo.exif.FocalLength.split("/").pop())).toFixed(2)+"mm");
		$("#Viewer > .infoView > .exif > .setting > .ev").html(photo.exif.ExposureBiasValue ? photo.exif.ExposureBiasValue + "EV" : "-");
		$("#Viewer > .infoView > .exif > .setting > .lens").html(photo.exif.COMPUTED.ApertureFNumber);
		$("#Viewer > .infoView > .exif > .setting > .opentime").html(photo.exif.ExposureTime);
		
		$("#Viewer > .infoView > .title").html(photo.title);
		$("#Viewer > .infoView > .time").html(photo.time);
		$("#Viewer > .infoView > .description").html(photo.description);
		
		if (gViewerMap == null) gViewerMap = new google.maps.Map(document.getElementById("ViewerMap"),{center:new google.maps.LatLng(0,0),zoom:14});
		if (gViewerMapPin != null) gViewerMapPin.setMap(null);
		
		gViewerMap.setCenter(new google.maps.LatLng(photo.latitude,photo.longitude));
		gViewerMapPin = new google.maps.Marker({
			position:gViewerMap.getCenter(),
			map:gViewerMap,
			draggable:false,
			icon:"./images/pin.png",
			optimized:false
		});
	}
	
	function loadPin() {
		var bounds = gMap.getBounds();
		$.ajax({
			type:"POST",
			url:"./exec/GetPins.php",
			data:{longitude:{start:bounds.getSouthWest().lng(),end:bounds.getNorthEast().lng()},latitude:{start:bounds.getNorthEast().lat(),end:bounds.getSouthWest().lat()},area:{width:$("#Map").width(),height:$("#Map").height()}},
			dataType:"json",
			success:function(result) {
				while (gPins.length > 0) {
					gPins.shift().setMap(null);
				}
				$(".balloonArea").remove();
				
				for (var i=0, loop=result.pins.length;i<loop;i++) {
					var position = new google.maps.LatLng(result.pins[i].latitude,result.pins[i].longitude);
					
					var pin = new google.maps.Marker({
						position:position,
						map:gMap,
						draggable:false,
						data:result.pins[i],
						icon:"./images/pin.png#"+result.pins[i].x+","+result.pins[i].y,
						optimized:false,
						animation:google.maps.Animation.DROP
					});
					google.maps.event.addListener(pin,"mouseover",function(a,b,c) {
						if (this.animating == true) return;
						$(".balloonArea").remove();
						
						var pin = $($("img[src='./images/pin.png#"+this.data.x+","+this.data.y+"']").get(0));
						
						var balloonArea = $("<div>").addClass("balloonArea");
						var balloon = $("<div>").addClass("balloon");
						balloon.append($("<div>").addClass("left"));
						var center = $("<div>").addClass("center").append($("<span>").addClass("text").html("총 "+this.data.area.photos+"개의 사진"));
						var arrow = $("<span>").addClass("arrow");
						center.append(arrow);
						balloon.append(center);
						balloon.append($("<div>").addClass("right"));
						
						balloonArea.append(balloon);
						pin.parent().parent().append(balloonArea);
						balloonArea.css("top",pin.parent().position().top-40);
						balloonArea.css("left",pin.parent().position().left-((balloon.width() - pin.parent().width())/2));
					});
					google.maps.event.addListener(pin,"mouseout",function(a,b,c) {
						$(".balloonArea").remove();
					});
					google.maps.event.addListener(pin,"click",function(a,b,c) {
						$.ajax({
							type:"POST",
							url:"./exec/GetPhotos.php?get=map",
							data:{longitude:{start:this.data.area.longitude.start,end:this.data.area.longitude.end},latitude:{start:this.data.area.latitude.start,end:this.data.area.latitude.end}},
							dataType:"json",
							success:function(result) {
								showViewer("map",result.photos);
							}
						});
					});
					
					gPins.push(pin);
				}
			}
		});
	}
	
	function loadCalendar(startDay,endDay) {
		$.ajax({
			type:"POST",
			url:"./exec/GetPhotos.php?get=calendar",
			data:{start:startDay,end:endDay},
			dataType:"json",
			success:function(result) {
				for (var i=0, loop=result.photos.length;i<loop;i++) {
					var day = $("#Calendar td[date='"+result.photos[i].date+"']");
					var area = day.find(".photo");
					var image = $("<img>").attr("src","/userfiles/calendar/"+result.photos[i].filename+".jpg");
					
					if (area.width() * result.photos[i].height < area.height() * result.photos[i].width) {
						var height = area.height();
						var width = height * result.photos[i].width / result.photos[i].height;
						image.css("marginLeft",(area.width()-width)/2);
					} else {
						var width = area.width();
						var height = width * result.photos[i].height / result.photos[i].width;
						image.css("marginTop",(area.height()-height)/2);
					}
					image.css("width",width).css("height",height);
					
					day.find(".photo").append($("<div>").css("width",area.width()).css("height",area.height()).append(image));
					day.data("photos").push(result.photos[i]);
				}
				$("#Calendar > .loader").hide();
			}
		});
	}
	
	function resizeRenderer() {
		$("#Map").height($(window).height() - $("#Header").height() - $("#Footer").height());
		
		if ($("#Calendar").is(":visible") == true) {
			$("#Calendar").height($(window).height() - $("#Header").height() - $("#Footer").height());
		
			var dayHeight = Math.floor(($("#Calendar").height()-40)/$("#Calendar").find("tr.days").length);
			for (var i=0, loop=$("#Calendar").find("tr.days").length;i<loop;i++) {
				if (i + 1 == loop) $($("#Calendar").find("tr.days")[i]).find("div.area").outerHeight($("#Calendar").height()-40-dayHeight*(loop-1));
				else $($("#Calendar").find("tr.days")[i]).find("div.area").outerHeight(dayHeight);
			}
			
			for (var i=0, loop=$("#Calendar").find("div.photo").length;i<loop;i++) {
				var area = $($("#Calendar").find("div.photo")[i]);
				area.width(area.parent().width());
				area.height(area.parent().height());
				
				var photos = area.parents("td").data("photos");
				if (photos.length > 0) {
					for (var j=0, loopj=photos.length;j<loopj;j++) {
						var image = $(area.find("img[src='/userfiles/calendar/"+photos[j].filename+".jpg']").get(0));
						
						if (area.width() * photos[j].height < area.height() * photos[j].width) {
							var height = area.height();
							var width = height * photos[j].width / photos[j].height;
							image.css("marginLeft",(area.width()-width)/2);
						} else {
							var width = area.width();
							var height = width * photos[j].height / photos[j].width;
							image.css("marginTop",(area.height()-height)/2);
						}
						image.css("width",width).css("height",height);
						image.parent().css("width",area.width()).css("height",area.height());
					}
				}
			}
		}
		
		if ($("#Viewer").is(":visible") == true) {
			$("#Viewer").height($(window).height() - $("#Header").height() - $("#Footer").height());
			$("#Viewer > .imageView").width($("#Viewer").width() - 300);
			$("#Viewer > .imageView").height($("#Viewer").height());
			$("#Viewer > .infoView").height($("#Viewer").height());
			$("#Footer > .viewer .thumbnail > .center").width($("#Footer > .viewer .thumbnail").width() - 10);
			
			if (gPosition != null && gImageList[gPosition] !== undefined) {
				var photo = gImageList[gPosition];
			
				var thumbnail = $($("#Footer > .viewer .thumbnail > .center > div.item").get(gPosition));
				var positionLeft = thumbnail.position().left - $("#Footer > .viewer .thumbnail > .center").position().left + $("#Footer > .viewer .thumbnail > .center").prop("scrollLeft");
				var scrollLeft = positionLeft - $("#Footer > .viewer .thumbnail > .center").width() / 2 + thumbnail.width() / 2 < 0 ? 0 : positionLeft - $("#Footer > .viewer .thumbnail > .center").width() / 2 + thumbnail.width() / 2;
				$("#Footer > .viewer .thumbnail > .center").prop("scrollLeft",scrollLeft);
				
				if ($("#Viewer > .imageView").width() * photo.height < $("#Viewer > .imageView").height() * photo.width) {
					var width = $("#Viewer > .imageView").width();
					var height = width * photo.height / photo.width;
					$("#Viewer > .imageView > img").css("marginTop",($("#Viewer > .imageView").height()-height)/2);
				} else {
					var height = $("#Viewer > .imageView").height();
					var width = height * photo.width / photo.height;
				}
				
				$("#Viewer > .imageView > img").css("width",width).css("height",height);
			}
			
			if ($("#Viewer > .imageView > .loader").length == 1) {
				$("#Viewer > .imageView > .loader").width($("#Viewer > .imageView").width()).height($("#Viewer > .imageView").height());
			}
		}
	}
	
	$(window).on("resize",function() {
		if (gResizeTimeout != null) {
			clearTimeout(gResizeTimeout);
			gResizeTimeout = null;
		}
		
		gResizeTimeout = setTimeout(resizeRenderer,500);
	});
	
	$(document).on("keyup",function(event) {
		if ($("#Viewer").is(":visible") == true) {
			if (event.keyCode == 37 && gPosition > 0) {
				showImage(gPosition - 1);
			}
			
			if (event.keyCode == 39 && gPosition < gImageList.length - 1) {
				showImage(gPosition + 1);
			}
		}
	});
	
	$(document).ready(function() {
		$("#Map").height($(window).height() - $("#Header").height() - $("#Footer").height());
		
		gMap = new google.maps.Map(document.getElementById("Map"),{center:new google.maps.LatLng(<?php echo $lastPhoto['latitude'] - 90; ?>,<?php echo $lastPhoto['longitude'] - 180; ?>),zoom:4,disableDefaultUI:true,keyboardShortcuts:false,maxZoom:17,minZoom:2});
		google.maps.event.addListener(gMap,"bounds_changed",function() {
			if (gPinLoadTimeout != null) {
				clearTimeout(gPinLoadTimeout);
				gPinLoadTimeout = null;
			}
			gPinLoadTimeout = setTimeout(loadPin,500);
		});
		google.maps.event.addListener(gMap,"zoom_changed",function() {
			$("#Footer > .map .slider").slider("value",gMap.getZoom());
		});
	});
	</script>
</body>


</html>