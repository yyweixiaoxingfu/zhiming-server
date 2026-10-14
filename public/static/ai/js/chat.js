try {

    var userrndstr, conversationid;
    var contextarray = [];
    var isstarted = true;
    var isalltext = false;
    var answer;
    var buttongo = true;
    var retrytimes = 0;
    var userScrolling = false;
    var scrollnow = 0;
    var previousScrollPosition = 0;
    var previousScrollHeight = 0;
    var layerqrcode = 0;
    var loading = 0;
    var currentTime;
    var freezegogogo = false;

    var defaults = {
        html: false,        // Enable HTML tags in source
        xhtmlOut: false,        // Use '/' to close single tags (<br />)
        breaks: false,        // Convert '\n' in paragraphs into <br>
        langPrefix: 'language-',  // CSS language prefix for fenced blocks
        linkify: true,         // autoconvert URL-like texts to links
        linkTarget: '',           // set target to open link in
        typographer: true,         // Enable smartypants and other sweet transforms
        _highlight: true,
        _strict: false,
        _view: 'html'
    };
    defaults.highlight = function (str, lang) {
        if (!defaults._highlight || !window.hljs) { return ''; }

        var hljs = window.hljs;
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(lang, str).value;
            } catch (__) { }
        }

        try {
            return hljs.highlightAuto(str).value;
        } catch (__) { }

        return '';
    };
    mdHtml = new window.Remarkable('full', defaults);

    mdHtml.renderer.rules.table_open = function () {
        return '<table class="table table-striped">\n';
    };

    mdHtml.renderer.rules.paragraph_open = function (tokens, idx) {
        var line;
        if (tokens[idx].lines && tokens[idx].level === 0) {
            line = tokens[idx].lines[0];
            return '<p class="line" data-line="' + line + '">';
        }
        return '<p>';
    };

    mdHtml.renderer.rules.heading_open = function (tokens, idx) {
        var line;
        if (tokens[idx].lines && tokens[idx].level === 0) {
            line = tokens[idx].lines[0];
            return '<h' + tokens[idx].hLevel + ' class="line" data-line="' + line + '">';
        }
        return '<h' + tokens[idx].hLevel + '>';
    };


    $(document).ready(function () {

        $("#question").on('keydown', function (event) {
            if (event.keyCode == 13) {
                gogogo();
                return false;
            }
        });

        $('.show-history').click(function () {
            $(".history").show();
            $("#main").toggleClass("goright");
            $(".show-history").hide();

        });
        $('.hide-history').click(function () {
            $(".history").hide();
            $("#main").toggleClass("goright");
            $(".show-history").show();

        });

        if ($.cookie('userrndstr')) {
            userrndstr = decryptCookie($.cookie('userrndstr'), 'ChatGPT');
            checkuserstatus();
        } else {
            userrndstr = randomString(16) + new Date().getTime();
        }

        $("#showlog").click(function () {
            layer.open({
                type: 1,
                title: '全部登录及对话日志',
                area: ['95%', '95%'],
                shade: 0.5,
                scrollbar: true,
                fixed: true, //设置为固定定位
                offset: '2.5%',
                content: '<iframe src="/ai/personal/show_log?userrndstr=' + userrndstr + "&rid=" + new Date().getTime() + '" style="width: 100%; height: 100%;"></iframe>'
            });
            return false;
        });

    });

    function checkuserstatus() {
        $.ajax({
            url: "/ai/personal/check_user_status?rid=" + randomString(10) + "&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    user_uid = data.uid;
                    user_email = data.email;
                    $("#rightbanner").html('<span style="text-decoration:none;cursor:pointer;" onclick="showuserinfo();">用户信息</span><span style="margin:0 10px;"></span><span class=" hover:text-blue-500 dark:hover:text-blue-400" style="text-decoration:none;cursor:pointer;" onclick="buy();">充值</span><span style="margin:0 10px;"></span><span class=" hover:text-blue-500 dark:hover:text-blue-400" style="text-decoration:none;cursor:pointer;" onclick="logout();">退出</span>');
                    if ($('.layui-layer-shade').length) {
                        layer.close(layerqrcode);
                    }
                    if (data.email == "") setTimeout("showuserinfo();", 500);
                    // console.log('checkuserstatus')
                    // 设置cookie
                    $.cookie('userrndstr', encryptCookie(userrndstr, 'ChatGPT'), {
                        expires: 30,
                        path: '/'
                    });
                    refreshquota();
                    $("#userpanel").css("display", "flex");
                    $(".show-history").show();
                    refreshhistory();
                } else {
                    if ($.cookie('userrndstr')) {
                        $.cookie('userrndstr', '', {
                            expires: 0,
                            path: '/'
                        });
                        layer.alert('您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                            icon: 2,
                            btn: ['确定'],
                            yes: function () {
                                location.reload();
                            }
                        });
                    } else {
                        setTimeout(checkuserstatus, 1000);
                    }
                }
            },
            error: function (xhr, status, error) {
                setTimeout(checkuserstatus, 1000);
            }
        });
    }

    function showuserinfo() {
        $("#mytitle").html('<div style="display: flex;"><div style="width: 100%; height: 50px;background-color: rgb(79, 128, 225);color: #fff;font-size: 16px;line-height: 50px;text-align: center;">用户信息维护</div></div>');
        $("#mycontent").html("<iframe style='width:100%;height:100%;' src='/ai/personal/user_info?userrndstr=" + userrndstr + "' scrolling='no'></iframe>");
        layeruserinfo = layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['300px', '350px'],
            offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
            content: $('#mydialog'),
            shadeClose: true,
        });
    }

    function refreshhistory() {
        $.ajax({
            url: "/ai/personal/get_user_conversation?type=all&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    $("#conversation").html("");
                    for (var i = 0; i < data.conversation.length; i++) {
                        $("#conversation").prepend('<li class="list-group-item list-group-item-action" style="padding-right:40px;overflow:hidden; position:relative;"><i class="fa fa-comments-o"></i><span style="margin-left:10px;cursor:pointer;" onclick="showconversation(\'' + data.conversation[i].id + '\');">' + data.conversation[i].question + '</span><i onclick="deleteconversation(\'' + data.conversation[i].id + '\',event);" class="fa fa-trash-o" style="position:absolute; right:20px;top:18px;cursor:pointer;"></i></li>');
                    }
                } else {
                    $("#conversation").html('<li class="list-group-item list-group-item-action" style="text-align:center;">无历史对话，请开始提问吧</li>');
                }
            },
            error: function (xhr, status, error) {
                setTimeout(refreshhistory, 1000);
            }
        });
    }

    function deleteconversation(itemId, event) {
        var x = event.clientX - 177;
        var y = event.clientY - 133;
        layer.confirm('确定要删除吗？', {
            btn: ['确定', '取消'],
            icon: 3,
            title: '确认删除',
            offset: [y + 'px', x + 'px']
        }, function (index) {
            layer.close(index);
            $.ajax({
                url: "/ai/personal/del_user_conversation?type=single&conversationid=" + itemId + "&userrndstr=" + userrndstr,
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        refreshhistory();
                    } else {
                        layer.msg(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
                }
            });

        });

    }

    function deleteallhistory() {
        layer.confirm('清空对话不会删除数据库中保存的记录，您还可以通过“查看全部对话日志”功能查看。<br>确定要清空所有对话吗？', {
            btn: ['确定', '取消'],
            icon: 3,
            title: '确认删除',
            offset: '40%'
        }, function (index) {
            layer.close(index);
            $.ajax({
                url: "/ai/personal/del_user_conversation?type=all&userrndstr=" + userrndstr,
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        refreshhistory();
                    } else {
                        layer.msg(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
                }
            });

        });

    }

    function clearchatpage() {
        $("#ChatPage").html("");
        $("#PromptPage").show();
        $("#ChatPage").hide();
        contextarray = [];
        $("#model option:first").prop("selected", true);
        $("#role option:first").prop("selected", true);
        $("#scene option:first").prop("selected", true);
        $(".divpanel select").css("background", "linear-gradient(to right, #4F80E1 55px, transparent 40px 100%)");
    }

    function showconversation(id) {
        $.ajax({
            url: "/ai/personal/get_user_conversation?type=single&conversationid=" + id + "&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    $("#ChatPage").html("");
                    conversationid = id;
                    contextarray = [];
                    for (var i = 0; i < data.conversation.length; i++) {

                        answer = randomString(16);
                        $("#PromptPage").hide();
                        $("#ChatPage").show();
                        var html = '<div class="dark:border-gray-900 border-b">' +
                            '<div class="flex flex-row py-4 px-4 sm:px-8 font-semibold text-gray-700 dark:text-gray-300 bg-neutral-100 dark:bg-gray-900 items-center" style="padding:10px 20px !important;">' +
                            '<span class="pr-4 grow-0 shrink-0 w-10 sm:w-12">您:</span><span title="双击复制文本" ondblclick="copyToClipboard(this.innerText);layer.msg(\'复制完成！\',{offset:\'50%\'});" class="grow dark:text-gray-300">' + data.conversation[i].question + '</span>' +
                            '</div></div>' +
                            '<div class="dark:border-gray-900">' +
                            '<div class="px-4 sm:px-8 py-4 flex flex-row text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800" style="padding:10px 20px !important;">' +
                            '<span class="font-semibold pr-4 grow-0 shrink-0 w-10 sm:w-12">AI:</span><span title="双击复制文本" ondblclick="copyToClipboard($(this).clone().find(\'button\').remove().end().text());layer.msg(\'复制完成！\',{offset:\'50%\'});" class="answer" id="' + answer + '" style="word-break:break-all;"><span class="blink"></span></span>' +
                            '</div></div></div>';

                        $("#ChatPage").append(html);
                        temptext = data.conversation[i].answer;
                        contextarray.push([encodeURIComponent(data.conversation[i].question), encodeURIComponent(data.conversation[i].answer)]);
                        contextarray = contextarray.slice(-5); //只保留最近5次对话作为上下文，以免超过最大tokens限制
                        if (temptext.split("\n").length == 1) {
                            temptext = temptext.replace(/\\n/g, '\n');
                        }
                        temptext = mdHtml.render(temptext);
                        $("#" + answer).html(temptext);
                        //hljs.highlightAll();
                        //if (islastletter) MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
                        $("#" + answer + " pre code").each(function () {
                            $(this).html("<button onclick='copycode(this);' class='codebutton'>复制</button>" + $(this).html());
                        });
                        document.body.scrollTop = document.body.scrollHeight;

                    }
                    if ($('.card-header').css('top') > '100px') {
                        $('.hide-history').click();
                    }
                } else {
                    refreshhistory();
                }
            },
            error: function (xhr, status, error) {
                setTimeout(refreshhistory, 1000);
            }
        });
    }

    function refreshquota() {
        $.ajax({
            url: "/ai/personal/check_user_quota?userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    var set_html =''; 
                    if(data.quota){
                        set_html += '剩余<span id="quota">'+data.quota+'</span> 积分';
                        
                    }
                    if(data.expiretime){
                        set_html += '&nbsp;&nbsp;&nbsp;&nbsp;<span id="expiretime">'+data.expiretime+'</span> 过期';
                    }
                    $("#userpanel").append(set_html);
                    // $("#quota").text(data.quota);
                    // $("#expiretime").text(data.expiretime);
                }
            },
            error: function (xhr, status, error) {
                setTimeout(refreshquota, 1000);
            }
        });
    }

    function showqrcode() {
        console.log(333);
        if (iswindowloginonly) {
            showuserlogin();
            layerqrcode = layer.open({
                type: 1,
                title: false,
                closeBtn: false,
                area: ['300px', '350px'],
                content: $('#mydialog'),
                shadeClose: true,
            });
            checkuserstatus();
        } else if (isthirdpartyloginonly) {
            console.log('thirdpartyloginurl')
            window.open(thirdpartyloginurl, '_blank');
        } else {
            var isWechat = /MicroMessenger/i.test(navigator.userAgent);
            if (isWechat) {
                //console.log('showqrcode')
                $.cookie('userrndstr', encryptCookie(userrndstr, 'ChatGPT'), {
                    expires: 30,
                    path: '/'
                });
                layer.msg("登录中，请稍候……", {
                    icon: 16,
                    shade: 0.4,
                    time: false //取消自动关闭
                });
                location.href = "http://" + window.location.hostname + "/s.php?m=1&s=" + userrndstr;
            } else {

                $("#mycontent").html("");
                $("#mycontent").qrcode("http://" + window.location.hostname + "/s.php?s=" + userrndstr);
                checkuserstatus();
                $("#mytitle").html(logintitleleft);
                layerqrcode = layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    area: ['300px', '350px'],
                    offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                    content: $('#mydialog'),
                    shadeClose: true,
                });

            }
        }
    }

    function showwxlogin() {
        $("#mytitle").html(logintitleleft);
        $("#mycontent").html("");
        $("#mycontent").qrcode("http://" + window.location.hostname + "/s.php?s=" + userrndstr);
    }

    function showuserlogin() {
        $("#mytitle").html(logintitleright);
        $("#mycontent").html("<iframe style='width:100%;height:100%;' src='/ai/index/user_login?userrndstr=" + userrndstr + "' scrolling='no'></iframe>");
    }

    function showshare() {
        if ($("#quota").text() == "") {
            layer.msg("请在登录后查看详情");
            return;
        }
        $("#mytitle").html("邀请有礼");
        $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>您的好友使用以下链接访问本站并登录后，您和您的好友将各获得" + freetryshare + "次查询次数。</p><p style='font-size:18px;text-align:left;line-height:35px;'>邀请越多，奖励越多，奖励没有上限。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;' onclick='copyToClipboard(this.innerText);layer.msg(\"网址已复制到剪贴板\");'>" + window.location.protocol + "//" + window.location.hostname + "/index.php?i=" + user_uid + "</p>");
        layershare = layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['300px', '350px'],
            offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
            content: $('#mydialog'),
            shadeClose: true,
        });
    }

    function logout() {
        $.cookie('userrndstr', '', {
            expires: 0,
            path: '/'
        });
        location.reload();
    }


    function copyToClipboard(text) {
        var input = document.createElement('textarea');
        input.innerHTML = text;
        document.body.appendChild(input);
        input.select();
        var result = document.execCommand('copy');
        document.body.removeChild(input);
        return result;
    }

    function copycode(obj) {
        copyToClipboard($(obj).closest('code').clone().children('button').remove().end().text());
        layer.msg("复制完成！", { offset: "50%" });
    }

    // 加密cookie
    function encryptCookie(value, secret) {
        const encrypted = CryptoJS.AES.encrypt(value, secret);
        return encrypted.toString();
    }

    // 解密cookie
    function decryptCookie(value, secret) {
        const decrypted = CryptoJS.AES.decrypt(value, secret);
        return decrypted.toString(CryptoJS.enc.Utf8);
    }

    function setCookie(name, value) {
        var date = new Date();
        date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000)); // 一年的毫秒数
        var expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }

    function getCookie(name) {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            if (cookie.indexOf(name + '=') === 0) {
                return cookie.substring(name.length + 1, cookie.length);
            }
        }
        return null;
    }

    function randomString(len) {
        len = len || 32;
        var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678'; /****默认去掉了容易混淆的字符oOLl,9gq,Vv,Uu,I1****/
        var maxPos = $chars.length;
        var pwd = '';
        for (i = 0; i < len; i++) {
            pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
        }
        return pwd;
    }

    function send_post() {
        $("#question").val("AI正在思考……");
        $("#question").attr("disabled", true);
        $("#go").text(" 中止 ");
        loading = layer.msg('AI正在思考，请稍等片刻...', {
            icon: 16,
            shade: 0.4,
            skin: 'layui-layer-molv', //设置皮肤样式
            anim: 2, //设置动画效果
            time: false, //取消自动关闭
            area: ['250px', '60px'], //设置宽度和高度
            offset: '50%', //设置左边距和上边距为50%
            fixed: true, //设置为固定定位
            marginLeft: '-125px', //设置左边距为负的一半宽度
            marginTop: '-30px' //设置上边距为负的一半高度
        });

        setTimeout("checkloading(" + loading + ");", 60000);

        function draw() {
            $.get("/ai/picture/generate?user=" + userrndstr, function (data) {
                layer.close(loading);
                loading = 0;
                if (data.error) {
                    var errcode = data.error.code;
                    var errmsg = data.error.message;
                    switch (errcode) {
                        case "model_not_found":
                            errmsg = "没有使用此模型的权限";
                            break;
                        case "invalid_user":
                            errmsg = "用户不存在或已在其他地方登录";
                            break;
                        case "out_of_money":
                            errmsg = "账户余额不足，请充值";
                            break;
                        case "no_valid_apikey":
                            errmsg = "系统中未配置可用的API_KEY";
                            break;
                        case "invalid_api_key":
                            errmsg = "API-KEY不合法";
                            break;
                        case "context_length_exceeded":
                            errmsg = "问题和上下文长度超限，请重新提问或刷新页面再提问";
                            break;
                        case "rate_limit_reached":
                            errmsg = "同时访问用户过多，请稍后再试";
                            break;
                        case "access_terminated":
                            errmsg = "违规使用，API-KEY被封禁";
                            break;
                        case "no_api_key":
                            errmsg = "未提供API-KEY";
                            break;
                        case "insufficient_quota":
                            errmsg = "API-KEY余额不足";
                            break;
                        case "account_deactivated":
                            errmsg = "账户已禁用";
                            break;
                        case "server_overloaded":
                            errmsg = "OpenAI服务器超负荷，请重新发起请求";
                            break;
                        case "sql_injection":
                            errmsg = "您的提问触发了SQL注入攻击防护机制，请修改下问题重新提问";
                            break;
                        default:
                            errmsg = "错误类型：" + errcode + "<br>错误详情：" + errmsg;
                    }

                    if ((errcode == "model_not_found") || (errcode == "invalid_user") || (errcode == "out_of_money") || (errcode == "no_valid_apikey") || (errcode == "context_length_exceeded")) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == null) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == "unknown") {
                        $("#" + answer).html(decodeURIComponent(errmsg));
                    } else {
                        if (retrytimes < 5) {
                            retrytimes++;
                            send_post();
                        } else {
                            $("#" + answer).html(errmsg);
                        }
                    }
                    buttongo = true;
                    $("#question").val("");
                    $("#question").attr("disabled", false);
                    $("#go").text(" 发送 ");
                    if (!isMobile()) $("#question").focus();
                    return;

                } else {
                    $("#" + answer).html("<img onload='document.body.scrollTop = document.body.scrollHeight;' src='/ai/index/picture_proxy?url=" + encodeURIComponent(data.data[0].url) + "'>");
                    buttongo = true;
                    $("#question").val("");
                    $("#question").attr("disabled", false);
                    $("#go").text(" 发送 ");
                    if (!isMobile()) $("#question").focus();
                    refreshquota();
                    refreshhistory();
                }
            }, "json");
        }
        function streaming() {
            isstarted = true;
            isalltext = false;
            alltext = "";
            es = new EventSource("/ai/stream/get_stream?user=" + userrndstr);
            es.onerror = function (event) {
                layer.close(loading);
                loading = 0;
                es.close();
                buttongo = true;
                $("#" + answer).html("服务器访问超时或网络错误");
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile()) $("#question").focus();
                return;
            }
            es.onmessage = function (event) {

                if (event.data == "[DONE]") { //处理正常流程结束标志（其实下面也有一段结束标志处理代码，那个更准确，这段也可以删掉，暂时留着以防万一）
                    isalltext = true;
                    contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                    contextarray = contextarray.slice(-5);
                    es.close();
                    return true;
                }
                try {
                    json = JSON.parse(event.data);
                    if (json.hasOwnProperty("error")) { //处理错误信息

                        layer.close(loading);
                        loading = 0;
                        var errcode = json.error.code;
                        var errmsg = json.error.message;
                        switch (errcode) {
                            case "model_not_found":
                                errmsg = "没有使用此模型的权限";
                                break;
                            case "invalid_user":
                                errmsg = "用户不存在或已在其他地方登录";
                                break;
                            case "out_of_money":
                                errmsg = "账户余额不足，请充值";
                                break;
                            case "no_valid_apikey":
                                errmsg = "系统中未配置可用的API_KEY";
                                break;
                            case "invalid_api_key":
                                errmsg = "API-KEY不合法";
                                break;
                            case "context_length_exceeded":
                                errmsg = "问题和上下文长度超限，请重新提问或刷新页面再提问";
                                break;
                            case "rate_limit_reached":
                                errmsg = "同时访问用户过多，请稍后再试";
                                break;
                            case "access_terminated":
                                errmsg = "违规使用，API-KEY被封禁";
                                break;
                            case "no_api_key":
                                errmsg = "未提供API-KEY";
                                break;
                            case "insufficient_quota":
                                errmsg = "API-KEY余额不足";
                                break;
                            case "account_deactivated":
                                errmsg = "账户已禁用";
                                break;
                            case "model_overloaded":
                                errmsg = "OpenAI模型超负荷，请重新发起请求";
                                break;
                            case "server_overloaded":
                                errmsg = "OpenAI服务器超负荷，请重新发起请求";
                                break;
                            case "sql_injection":
                                errmsg = "您的提问触发了SQL注入攻击防护机制，请修改下问题重新提问";
                                break;
                            default:
                                errmsg = "错误类型：" + errcode + "<br>错误详情：" + errmsg;
                        }
                        es.close();
                        if ((errcode == "model_not_found") || (errcode == "invalid_user") || (errcode == "out_of_money") || (errcode == "no_valid_apikey") || (errcode == "context_length_exceeded")) {
                            $("#" + answer).html(errmsg);
                        } else if (errcode == null) {
                            $("#" + answer).html(errmsg);
                        } else if (errcode == "unknown") {
                            $("#" + answer).html(decodeURIComponent(errmsg));
                        } else {
                            if (retrytimes < 5) {
                                retrytimes++;
                                send_post();
                            } else {
                                $("#" + answer).html(errmsg);
                            }
                        }
                        isalltext = true;
                        buttongo = true;
                        $("#question").val("");
                        $("#question").attr("disabled", false);
                        $("#go").text(" 发送 ");
                        if (!isMobile()) $("#question").focus();
                        return true;
                    } else if (json.choices[0].delta.hasOwnProperty("content")) { //处理正常流程的data
                        if (alltext == "") {
                            alltext = json.choices[0].delta.content.replace(/^\n+/, '');
                        } else {
                            alltext += json.choices[0].delta.content;
                        }
                    } else if (json.choices[0].finish_reason == "stop") { //处理正常流程结束标志
                        isalltext = true;
                        contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                        contextarray = contextarray.slice(-5);
                        es.close();
                        return true;
                    }

                    if (isstarted) {
                        previousScrollPosition = document.body.scrollTop;
                        userScrolling = false;
                        isstarted = false;
                        layer.close(loading);
                        loading = 0;
                        str_ = '';
                        i = 0;
                        strforcode = '';
                        timewait = 30;
                        timer = setInterval(() => {
                            if (previousScrollPosition > document.body.scrollTop) {
                                userScrolling = true;
                            }
                            let newalltext = alltext;
                            let islastletter = false;
                            //有时服务器错误地返回\\n作为换行符，尤其是包含上下文的提问时，这行代码可以处理一下。
                            if (newalltext.split("\n").length == 1) {
                                newalltext = newalltext.replace(/\\n/g, '\n');
                            }
                            //用newalltext.length - 3是因为如果服务端返回\\n或\n时拆成两条消息了，str_会把单个\当成字符。
                            if (str_.length < (newalltext.length - 3)) {
                                str_ += newalltext[i++];
                                strforcode = str_;
                                if ((str_.split("```").length % 2) == 0) {
                                    strforcode += "\n```\n";
                                } else {
                                    strforcode += "_";
                                }
                                if ((newalltext.length - 3 - str_.length) > 10) {
                                    timewait = 20;
                                }
                                if ((newalltext.length - 3 - str_.length) > 20) {
                                    timewait = 10;
                                }
                                if ((newalltext.length - 3 - str_.length) > 30) {
                                    timewait = 5;
                                }
                                if ((newalltext.length - str_.length) > 50) {
                                    str_ = newalltext.slice(0, -50);
                                    i = str_.length;
                                }
                            } else {
                                if (isalltext) {
                                    clearInterval(timer);
                                    strforcode = newalltext;
                                    if (isanswersensor) {
                                        loading = layer.msg('正在审核AI的回答是否合规...', {
                                            icon: 16,
                                            shade: 0.4,
                                            skin: 'layui-layer-molv', //设置皮肤样式
                                            anim: 2, //设置动画效果
                                            time: false, //取消自动关闭
                                            area: ['270px', '60px'], //设置宽度和高度
                                            offset: '50%', //设置左边距和上边距为50%
                                            fixed: true, //设置为固定定位
                                            marginLeft: '-135px', //设置左边距为负的一半宽度
                                            marginTop: '-30px' //设置上边距为负的一半高度
                                        });
                                        $.ajax({
                                            cache: true,
                                            type: "POST",
                                            url: "/ai/index/baidu_content_sensor",
                                            data: {
                                                text: encodeURIComponent(strforcode),
                                            },
                                            dataType: "json",
                                            success: function (data) {
                                                layer.close(loading);
                                                loading = 0;
                                                if (!data.isvalid) {
                                                    strforcode = "AI的回答未通过内容审核！";
                                                    renderhtml(strforcode);
                                                }
                                            }
                                        });
                                    }
                                    renderhtml(strforcode);

                                    islastletter = true;
                                    buttongo = true;
                                    $("#question").val("");
                                    $("#question").attr("disabled", false);
                                    $("#go").text(" 发送 ");
                                    if (!isMobile()) $("#question").focus();
                                    refreshquota();
                                    if (contextarray.length == 1) refreshhistory();
                                }
                            }

                            renderhtml(strforcode);
                        }, timewait);
                    }
                } catch (e) {
                    layer.close(loading);
                    loading = 0;
                    isalltext = true;
                    buttongo = true;
                    es.close();
                    $("#" + answer).html("异常错误：" + event.data);
                    $("#question").val("");
                    $("#question").attr("disabled", false);
                    $("#go").text(" 发送 ");
                    if (!isMobile()) $("#question").focus();
                    return false;
                }
            }
        }

        if (contextarray.length == 0) {
            conversationid = Date.now();
        }

        if (conversationid == undefined || conversationid == null || conversationid == "") {
            conversationid = Date.now();
        }

        $.ajax({
            cache: true,
            type: "POST",
            url: "/ai/index/set_session",
            data: {
                message: encodeURIComponent(prompt),
                model: $("#model").val(),
                role: $("#role").val(),
                context: JSON.stringify(contextarray),
                user: userrndstr,
                conversationid: conversationid,
            },
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    if ($("#model option:selected").text().indexOf("画") >= 0) {
                        draw();
                    } else {
                        streaming();
                    }
                } else {
                    layer.msg(data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('Set Session Error:', error);
                layer.close(loading);
                layer.msg('请求失败，请重试');
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile()) $("#question").focus();
            }
        });

    }

    function renderhtml(strforcode) {
        newalltext = mdHtml.render(strforcode);
        $("#" + answer).html(newalltext);
        //hljs.highlightAll();
        //if (islastletter) MathJax.Hub.Queue(["Typeset", MathJax.Hub]);
        $("#" + answer + " pre code").each(function () {
            $(this).html("<button onclick='copycode(this);' class='codebutton'>复制</button>" + $(this).html());
        });

        //var converter = new showdown.Converter();
        //$("#" + answer).html(converter.makeHtml(strforcode));
        if (!userScrolling) {
            if (previousScrollHeight !== document.body.scrollHeight) {
                document.body.scrollTop = document.body.scrollHeight;
                previousScrollPosition = document.body.scrollTop;
                previousScrollHeight = document.body.scrollHeight;
            }
        }
    }

    function gogogo() {
        if ($("#quota").text() == "") {
            layer.msg("请登录后再提问，谢谢");
            return;
        }
        if ($("#quota").text() < 1) {
            layer.msg("您的账户余额已不足，请充值后再提问，谢谢");
            return;
        }
        var d1 = new Date($("#expiretime").text());
        d1.setDate(d1.getDate() + 1);
        var today = new Date();
        if (d1 < today) {
            layer.confirm('您的余额已过期，充值任意金额可以延长有效期。<br>充值后查询次数以现有的查询次数为基准进行累加，有效期从今天开始计算进行延长。', {
                btn: ['确定'],
                icon: 3,
                title: '余额过期提示',
            }, function (index) {
                layer.close(index);
                buy();
            });
            return;
        }
        if (freezegogogo) return; //防止短时间内连续点击“发送”按钮造成本函数多次运行而造成不停请求的情况
        freezegogogo = true;
        if (!buttongo) {
            if ($("#model option:selected").text().indexOf("画") < 0) {
                clearInterval(timer);
                buttongo = true;
                if (!isalltext) {
                    contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                    contextarray = contextarray.slice(-5);
                    es.close();
                }
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile()) $("#question").focus();
                refreshquota();
                if (contextarray.length == 1) refreshhistory();
                freezegogogo = false;
                return
            }
        } else {
            prompt = $("#question").val();

            if (prompt == "") {
                layer.msg("请输入您的问题", {
                    icon: 5
                });
            } else {
                if (isquestionfilter) {
                    $.ajax({
                        cache: true,
                        type: "POST",
                        url: "/ai/index/keywordfilter",
                        data: {
                            text: encodeURIComponent(prompt),
                        },
                        dataType: "json",
                        success: function (data) {
                            if (data.newtext) {
                                prompt = decodeURIComponent(data.newtext);
                                preparetoask();
                            }
                        }
                    });
                } else {
                    preparetoask();
                }
            }

        }
        freezegogogo = false;
    }

    function preparetoask() {
        if (isquestionsensor) {
            loading = layer.msg('正在审核您的问题是否合规...', {
                icon: 16,
                shade: 0.4,
                skin: 'layui-layer-molv', //设置皮肤样式
                anim: 2, //设置动画效果
                time: false, //取消自动关闭
                area: ['270px', '60px'], //设置宽度和高度
                offset: '50%', //设置左边距和上边距为50%
                fixed: true, //设置为固定定位
                marginLeft: '-135px', //设置左边距为负的一半宽度
                marginTop: '-30px' //设置上边距为负的一半高度
            });
            $.ajax({
                cache: true,
                type: "POST",
                url: "/ai/index/baidu_content_sensor",
                data: {
                    text: encodeURIComponent(prompt),
                },
                dataType: "json",
                success: function (data) {
                    layer.close(loading);
                    loading = 0;
                    if (!data.isvalid) {
                        layer.msg("您录入的问题未通过内容审核！");
                        return;
                    } else {
                        starttoask();
                    }
                }
            });
        } else {
            starttoask();
        }
    }

    function starttoask() {
        buttongo = false;
        answer = randomString(16);
        $("#PromptPage").hide();
        $("#ChatPage").show();
        var html = '<div class="dark:border-gray-900 border-b">' +
            '<div class="flex flex-row py-4 px-4 sm:px-8 font-semibold text-gray-700 dark:text-gray-300 bg-neutral-100 dark:bg-gray-900 items-center" style="padding:10px 20px !important;">' +
            '<span class="pr-4 grow-0 shrink-0 w-10 sm:w-12">您:</span><span title="双击复制文本" ondblclick="copyToClipboard(this.innerText);layer.msg(\'复制完成！\');" class="grow dark:text-gray-300" id="q' + answer + '" style="word-break:break-all;"></span>' +
            '</div></div>' +
            '<div class="dark:border-gray-900">' +
            '<div class="px-4 sm:px-8 py-4 flex flex-row text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800" style="padding:10px 20px !important;">' +
            '<span class="font-semibold pr-4 grow-0 shrink-0 w-10 sm:w-12">AI:</span><span title="双击复制文本" ondblclick="copyToClipboard($(this).clone().find(\'button\').remove().end().text());layer.msg(\'复制完成！\');" class="answer" id="' + answer + '" style="word-break:break-all;"><span class="blink"></span></span>' +
            '</div></div></div>';

        $("#ChatPage").append(html);
        for (var j = 0; j < prompt.length; j++) {
            $("#q" + answer).text($("#q" + answer).text() + prompt[j]);
        }
        retrytimes = 0;

        send_post();
    }

    function isMobile() {
        const userAgent = navigator.userAgent.toLowerCase();
        const mobileKeywords = ['iphone', 'ipod', 'ipad', 'android', 'windows phone', 'blackberry', 'nokia', 'opera mini', 'mobile'];
        for (let i = 0; i < mobileKeywords.length; i++) {
            if (userAgent.indexOf(mobileKeywords[i]) !== -1) {
                return true;
            }
        }
        return false;
    }

    function rechargebycard() {
        $.ajax({
            url: "rechargebycard.php?cardpass=" + $("#cardpass").val() + "&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    refreshquota();
                    layer.msg("充值成功");
                } else {
                    layer.msg(data.message);
                }
            },
            error: function (xhr, status, error) {
                layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
            }
        });
    }


    $(".divpanel select").on({
        focus: function () {
            $(this).css("background", "#4F80E1");
        },
        blur: function () {
            if ($(this).find('option:first').is(':selected')) {
                $(this).css("background", "linear-gradient(to right, #4F80E1 55px, transparent 40px 100%)");
            }
        },
        change: function () {
            if ($(this).find('option:first').is(':selected')) {
                $(this).css("background", "linear-gradient(to right, #4F80E1 55px, transparent 40px 100%)");
            }
        }
    });

    $(document).on('click', '.product', function () {
        $(this).addClass("selected").siblings().removeClass("selected");
        $("#productid").val($(this).attr("productid"));
    });

    function showwxpayqrcode() {
        var productid = $('#productid').val();
        if (productid == "") {
            layer.msg("请选择充值卡类型");
            return false;
        }
        layer.close(buylayer);
        buylayer = 0;
        var isWechat = /MicroMessenger/i.test(navigator.userAgent);
        if (isWechat) {
            location.href = "http://" + window.location.hostname + "/wxpay.php?m=1&p=" + productid + "&s=" + userrndstr;
        } else {
            $("#mycontent").html("");
            $("#mycontent").qrcode("http://" + window.location.hostname + "/wxpay.php?p=" + productid + "&s=" + userrndstr);

            $("#mytitle").html("请用微信扫码支付");
            layerqrcode = layer.open({
                type: 1,
                title: false,
                closeBtn: false,
                area: ['300px', '350px'],
                offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                content: $('#mydialog'),
                shadeClose: true,
                success: function (layero, index) {
                    // 点击任意区域自动隐藏
                    layero.on('click', function () {
                        layer.close(index);
                    });
                }
            });
            currentTime = new Date().toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Shanghai'
            }).replace(/\//g, '-').replace(',', '');
            checkwxpaystatus();
        }
    }

    function showalipayqrcode() {
        var productid = $('#productid').val();
        if (productid == "") {
            layer.msg("请选择充值卡类型");
            return false;
        }
        layer.close(buylayer);
        buylayer = 0;

        $.get("alipayqrcodeurl.php?p=" + productid + "&s=" + userrndstr, function (data) {
            if (data.error) {
                alipayqrcodeurl = '支付宝后台参数配置不正确，请联系管理员。错误提示：' + data.errmsg;
            } else {
                alipayqrcodeurl = data.alipayqrcodeurl;
            }

            var isWechat = /MicroMessenger/i.test(navigator.userAgent);
            if (isWechat) {
                $("#mytitle").html("支付宝");
                if (/iPhone/i.test(navigator.userAgent)) {
                    $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>点击下面的链接即可复制网址，在手机浏览器中访问即可跳转到支付宝进行支付。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;word-wrap:break-word;' onclick='location.href=\"" + alipayqrcodeurl + "\";'>" + alipayqrcodeurl + "</p>");
                } else {
                    $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>点击下面的链接即可复制网址，在手机浏览器中访问即可跳转到支付宝进行支付。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;word-wrap:break-word;' onclick='copyToClipboard(this.innerText);layer.msg(\"网址已复制到剪贴板\");'>" + alipayqrcodeurl + "</p>");
                }
                layerqrcode = layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    area: ['300px', '350px'],
                    offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                    content: $('#mydialog'),
                    shadeClose: true,
                    close: function () { alert(1); },
                });
            } else {
                $("#mytitle").html("请用支付宝扫码支付");
                $("#mycontent").html("");
                $("#mycontent").qrcode(alipayqrcodeurl);
                layerqrcode = layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    area: ['300px', '350px'],
                    offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                    content: $('#mydialog'),
                    shadeClose: true,
                    success: function (layero, index) {
                        // 点击任意区域自动隐藏
                        layero.on('click', function () {
                            layer.close(index);
                        });
                    }
                });
            }
            currentTime = new Date().toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Shanghai'
            }).replace(/\//g, '-').replace(',', '');
            checkalipaystatus();
        }, "json");
    }

    function showrechargecard() {
        $("#buypanel").html('<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height:100%;"><input type="text" id="cardpass" placeholder="充值卡密码" maxlength="20" style="width: 80%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px;" oninput="this.value=this.value.replace(/\\s+/g,\'\')"><button onclick="rechargebycard();" style="background-color: #007bff; color: #fff; border: none; padding: 10px 20px; margin:20px; font-size: 16px; cursor: pointer;">充值</button></div>');
    }

    function gotoshop() {
        var productid = $('#productid').val();
        if (productid == "") {
            layer.msg("请选择充值卡类型");
            return false;
        }
        showrechargecard();
        window.open('shop.php?p=' + productid, '_blank');
    }
    function checkwxpaystatus() {
        $.ajax({
            url: "checkwxpaystatus.php?userrndstr=" + userrndstr + "&begintime=" + currentTime,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    if ($('.layui-layer-shade').length) {
                        layer.close(layerqrcode);
                    }
                    refreshquota();
                } else {
                    if ($('.layui-layer-shade').length) {
                        setTimeout(checkwxpaystatus, 1000);
                    }
                }
            },
            error: function (xhr, status, error) {
                setTimeout(checkwxpaystatus, 1000);
            }
        });
    }
    function checkalipaystatus() {
        $.ajax({
            url: "checkalipaystatus.php?userrndstr=" + userrndstr + "&begintime=" + currentTime + "&rid=" + randomString(10),
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    if ($('.layui-layer-shade').length) {
                        layer.close(layerqrcode);
                    }
                    refreshquota();
                } else {
                    if ($('.layui-layer-shade').length) {
                        setTimeout(checkalipaystatus, 1000);
                    }
                }
            },
            error: function (xhr, status, error) {
                setTimeout(checkalipaystatus, 1000);
            }
        });
    }

    function checkloading(nowloading) {
        if (loading == nowloading) {
            layerconfirm = layer.confirm('已等待超过1分钟，是否中止本次提问？', {
                btn: ['中止提问', '再等一分钟'],
                icon: 3,
                title: '确认删除',
                offset: '50%',
                cancel: function () {
                    setTimeout("checkloading(" + nowloading + ");", 60000);
                    layerconfirm = 0;
                }
            }, function (index) {
                layer.close(index);
                layer.close(loading);
                loading = 0;
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile()) $("#question").focus();
                layerconfirm = 0;
            });
        }
    }
} catch (err) {
    console.log(err)
    //layer.msg(err);
}
