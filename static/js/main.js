$(window).scroll(function(){
    var url = window.location.href;
    if(url.indexOf('/case/list') < 0 ) {
	return;
    }
    var scrollTop = $(this).scrollTop();
    var scrollHeight = $(document).height();
    var windowHeight = $(this).height();
    if(scrollTop + windowHeight >= scrollHeight-50) {
		var child='';
		var lastChild=$('tbody tr:last-child');
		if(parseInt(localStorage.getItem('more')) != 1) {
			return;
		}
		
		var pn = localStorage.getItem("pn") == null ? 0 :localStorage.getItem("pn");
		var rn = 10;
		pn= parseInt(pn) + rn;
		localStorage.setItem("pn",pn);
		$.get(
			'/case/list',
			{
				'product' :localStorage.getItem('product'),
				'pn'  : pn,
				'api' : 1
			},
			function(data) {
				data  = JSON.parse(data);
				if (data.caselist.length > 0) {
					$.each(data.caselist,function(key ,val) {
						val.testCnt = val.func.length;
						child = '<tr id=tr'+ (pn+key+1)+'>' +
								'<td colspan=1 rowspan=' + val.testCnt + '>' + (pn+key+1) +
								'<div class="checkbox">' +
								'<label><input type=checkbox value="1" name="isSelected">' +
								'</label></div></td>' +
								'<td colspan=2 rowspan=' + val.testCnt + '>' +val.file +'</td>' +
								'<td colspan=1 rowspan=' + val.testCnt + '>' + val['class']['auth'] +'</td>' +
								'<td colspan=2 rowspan=' + val.testCnt + '>' + val['class']['Desc'] +'</td>' +
								'<td colspan=1 rowspan=' + val.testCnt + '>' + val['class']['case'] +'</td>' +
								'<td colspan=3><a href=# name="case_detail">' + val.func[0]['func']+'</a>' +
								(val.testCnt>1 ? '<i class="icon-chevron-down" style="float:right" onclick="fold(' + (pn+key+1)+');"></i>':'')+
								'</td><td colspan=2 rowspan=' + val.testCnt + '>备注' + '</td></tr>';
								if (val.testCnt > 1) {
									$.each(val.func, function(k,v){ 
										if(k > 0) {
											child += '<tr class="subcase"><td colspan=3 style="display:none">' +
													  '<a href="#" name="case_detail">' + v.func + '</a>' +'</td></tr>'
										}
									});
								}
						$("#groupTbl").append(child);
						$("#groupTbl").append(lastChild);
					});
				}else {
					localStorage.setItem('more', 0);			
				}
			}
		)
	}
});


function showCoverage(taskid, product) {
	$('tbody:eq(1)').html('');
	$.post(
		'/coverage/detail',
		{
			taskId : taskid,
			product : product
		},
		function (data) {
			if(data.length < 3 )  {
				$('#coverage_list').html('<p class="text-center">无详细数据</p>');
			}else {
				dataArr = JSON.parse(data);
				for(var i =0 ; i < dataArr.length ; i++) {
					child = '<tr><td colspan=3><a target="_blank" href="/coverage/show?filename='+ dataArr[i].filename + '&taskId=' +taskid+'&product=' + product + '">' + dataArr[i].filename + '</td>' +
						'<td>'+ dataArr[i].totalNum + '</td>' +
						'<td>'+ dataArr[i].validNum + '</td>' +
						'<td>'+ dataArr[i].covNum + '</td>';
					if (dataArr[i].coverage> 90) {
						child += "<td bgcolor='green'>"+dataArr[i].coverage+"%</td>";
					}else if( dataArr[i].coverage > 60) {
						child += "<td bgcolor='yellow'>"+dataArr[i].coverage+"%</td>";
					}else if ( dataArr[i].coverage > 30) {
						child += "<td bgcolor='red'>"+dataArr[i].coverage+"%</td>";
					}else if ( dataArr[i].coverage >0) {
						child += "<td bgcolor='grey'>"+dataArr[i].coverage+"%</td>";
					}else {
						child += "<td bgcolor='blue'>"+dataArr[i].coverage+"%</td>"; 
					}
					child += '</tr>';
					$("tbody:eq(1)").append(child);
				}
			}
		}
		
	)
}

function freshCList(pn) {
	$.post(
		'/coverage/list',
		{
			pn : pn
		},
		function (data) {
			for(var i=0 ; i< 11; i++) {
				$('tbody:eq(0)').find('tr:eq(1)').remove();
			}
			dataArr = JSON.parse(data);
			var statusCss = new Array('success', 'error', 'warning');
			for (var i =0 ; i< dataArr.length ; i++) {
				child = '<tr class=' + statusCss[i%3]+ '><td>' +
				'<a href="#" name="report_detail" data-html="true" rel="popover" data-trigger="hover" data-content="文件数:'+dataArr[i].report.filenum + '<br>代码总行数:' + dataArr[i].report.totalnum + '覆盖总行数:' + dataArr[i].report.covnum + '<br>覆盖率:' + dataArr[i].report.rate +'" data-original-title="汇总数据" onclick=showCoverage('+ dataArr[i].taskid + ',"'+dataArr[i].product +'");>' +
				'<font size ="3">' + dataArr[i].owner +' + '+ dataArr[i].taskid +' + '+ dataArr[i].product + '</font></a>' +
				'</td></tr>';
				$('tbody:eq(0)').append(child);
			}
			if( pn == 0 ) {
         			$('tbody:eq(0)').append('<tr><td><a href="#" onclick=freshCList(' +(pn+10) +')>下一页</a></td></tr>');
			}
			if(dataArr.length == 10 && pn !=0) {
         			$('tbody:eq(0)').append('<tr><td><a href="#" onclick=freshCList(' + (pn-10) + ')> 上一页</a>  <a href="#" onclick=freshCList(' +(pn+10) +')>下一页</a></td></tr>');
		        }
			if (dataArr.length != 10 && pn !=0 ) {
		        	$('tbody:eq(0)').append('<tr><td><a href="#" onclick=freshCList(' + (pn-10) + ')> 上一页 </a></td></tr>');
        		}
		}
	);
}

