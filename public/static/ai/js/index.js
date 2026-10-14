try {
    var user_uid, user_email;
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
    var layerqrcode = 0;
    var loading = 0;
    var currentTime;
    var freezegogogo = false;
    var layerconfirm = 0;
    var refreshhistorytime = 0;
    var nextrefreshhistorytime = 1;
    var check_chat_exist = false;
    var timewait = 30;
    var userforcestop = false;
    var tips;
    var isWechat = /MicroMessenger/i.test(navigator.userAgent);
    var isMobile = 0;
    var lastpicurl = "";
    var revised_prompt = "";
    var reasoning_content = "";

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
    var audio_context;
    var recorder;
    var isrecordersuccess = 0;
    var isasrquestion = 0;
    var audioarray = [];
    var nowReadingId = 0;
    function containsFormula(text) {
        // 检查是否存在成对的 ```，如果存在则直接返回 false
        const codeBlockPattern = /```[\s\S]*?```/g;
        if (codeBlockPattern.test(text)) {
            return false; // 如果存在代码块，直接返回 false
        }
        // 匹配 LaTeX 公式的正则表达式
        const formulaPattern = /\$\s*(.+?)\s*\$|\$\$\s*(.+?)\s*\$\$|\\\(\s*(.+?)\s*\\\)|\\\[\s*(.+?)\s*\\\]/g; // 匹配 $...$ 或 \[...\]
        // 检查是否有匹配的公式
        return formulaPattern.test(text);
    }
    function stopAllAudio() {
        if (nowReadingId) {
            for (var i = 0; i < audioarray.length; i++) {
                if (audioarray[i][0] == nowReadingId) {
                    layer.msg('暂停朗读', { skin: 'custom_alert1' });
                    audioarray[i][1].pause();
                    $("#" + nowReadingId).prev('div').find('div:contains("暂停朗读")').text("继续朗读");
                    nowReadingId = 0;
                }
            }
        }
    }
    function initRecording() {
        try {
            audio_context = new AudioContext({ sampleRate: 8000 });
            console.log(audio_context);
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                gumStream = stream;
                input = audio_context.createMediaStreamSource(stream);
                recorder = new Recorder(input, { numChannels: 1 });
                gumStream.getAudioTracks()[0].stop();
            }).catch(function (err) {
                layer.msg('找不到录音设备');
                isrecordersuccess = 0;
                return false;
            });
            isrecordersuccess = 1;
        } catch (e) {
            console.log(e);
            layer.msg('您的浏览器不支持录制声音');
            return false;
        }
    }

    function startRecording() {
        stopAllAudio();
        if (isrecordersuccess) {
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                gumStream = stream;
                input = audio_context.createMediaStreamSource(stream);
                recorder = new Recorder(input, { numChannels: 1 });
                recorder && recorder.record();
            }).catch(function (err) {
                layer.msg('找不到录音设备');
                isrecordersuccess = 0;
            });
        }
    }

    function stopRecording() {
        if (isrecordersuccess) {
            recorder && recorder.stop();
            sendSoundToServer();
            try {
                recorder.clear();
                gumStream.getAudioTracks()[0].stop();
            } catch (e) {
                layer.msg('录音不成功，请确认是否授权使用麦克风');
            }
        }
    }

    function sendSoundToServer() {
        recorder && recorder.exportWAV(function (blob) {
            var formData = new FormData();
            formData.append('file', blob);
            loading = layer.msg('语音识别中，请稍等片刻...', {
                icon: 16,
                shade: 0.4,
                skin: 'layui-layer-molv',
                anim: 2,
                time: false,
                area: ['250px', '60px'],
                offset: '50%',
                fixed: true,
                marginLeft: '-125px',
                marginTop: '-30px'
            });
            $.ajax({
                url: '/ai/asr/index',
                type: 'POST',
                data: formData,
                processData: false,  // important
                contentType: false,  // important
                success: function (data) {
                    layer.close(loading);
                    loading = 0;
                    asrprocess(data);
                },
                error: function (error) {
                    layer.close(loading);
                    loading = 0;
                    alert('语音识别错误：' + error.responseText);
                }
            });
        });
    }

    function asrprocess(data) {
        try {
            json = JSON.parse(data);
            if (json.hasOwnProperty("error")) { //处理错误信息
                var errcode = json.error.code;
                var errmsg = json.error.message;

                errmsg = get_error_msg(errcode, errmsg);
                layer.msg(errmsg);
                return;
            } else if (json.hasOwnProperty("text")) {
                if (json.text.trim() == "") {
                    layer.msg("没有识别到任何文字，请重试");
                } else {
                    user_output(json.text);
                    gogogo(json.text);
                }
                return;
            }
        } catch (e) {
            layer.msg("语音识别错误：" + data);
            return;
        }
    }

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

    mdHtml = new window.Remarkable('commonmark', defaults);

    $(document).on('click', '.product', function () {
        $(this).addClass("selected").siblings().removeClass("selected");
        $("#productid").val($(this).attr("productid"));
    });

    $(document).ready(function () {
        isMobile = ismobilephone();
        $(document).click(function (event) {
            var layer = $(".home_prompt-hints");
            var layer2 = $("#scene_button");
            var layer3 = $("#model_button");
            var target = event.target;

            // 判断被点击的元素是否是图层本身或者图层内部的元素
            if (target === layer[0] || target === layer2[0] || target === layer3[0] || layer.has(target).length || layer2.has(target).length || layer3.has(target).length) {
                return;
            }
            $(".home_prompt-hints").hide();
        });

        //   document.body.addEventListener('touchmove', function (e) {  //防止微信拖动，效果不太好
        //       if (!$(e.target).hasClass('home_sidebar-body') && !$(e.target).hasClass('home_chat-body') && !$(e.target).hasClass('layui-menu') && !$(e.target).hasClass('home_chat-input-panel') && e.target.id != "question") {
        //           e.preventDefault();
        //       }
        //   }, { passive: false });

        $('#question').click(function () {
            checkloginstatus();
        });
        $('.home_sidebar-body').scroll(function () {
            var element = $(this);
            if (element.scrollTop() + element.innerHeight() + 10 >= element[0].scrollHeight) {
                // 滚动条已经滚动到最下方
                if (refreshhistorytime < nextrefreshhistorytime) {
                    refreshhistorytime++;
                    refreshhistory(refreshhistorytime);
                }
            }
        });

        // 登陆按钮
        $('.home_sidebar-bar-button').on('click', function () {
            showqrcode();
        });

        // 分享按钮
        if ($('#sharebutton').length) {
            $('#sharebutton').on('click', function () {
                showshare();
            });
        }

        // 新的聊天按钮
        $('#createchatbutton').on('click', function () {
            create_chat();
        });

        // 全部聊天日志按钮
        $('#chathistorybutton').on('click', function () {
            browse_chat();
        });

        // 收起聊天框按钮
        $('.home_mobile').on('click', function () {
            $('.home_sidebar').toggleClass('home_sidebar-show');
        });

        $('.window-header-main-title').on('click', function (e) {
            changeconversationtitle(conversationid);
        });

        // 导出按钮
        $('#divsharechat button').on('click', function () {
            $('#chat_out').toggle();
        });

        // 全屏按钮
        $('.max_min button').on('click', function () {
            togglefullscreen();
        });

        // 选择某个场景
        $('#scene_list_div').on('click', '.home_prompt-hint', function () {
            $('#scene_list_div').toggle();
            $('#question').val($(this).find('.home_hint-content').text());
            $('#question').trigger('input');
        });

        // 模型按钮
        $('#model_button').on('click', function () {
            $('#scene_list_div').hide();
            $('#model_list_div').toggle();
        });

        // 场景按钮
        $('#scene_button').on('click', function () {
            $('#model_list_div').hide();
            $('#scene_list_div').toggle();
        });

        // 上传图片按钮
        $('#uploadpicbutton').on('click', function () {
            if (checkloginstatus()) {
                uploadimage();
            }
        });

        // 发送语音按钮
        $('#sendvoicebutton').on('click', function () {
            if (checkloginstatus()) {
                switchRecordingStatus(this);
            }
        });

        // 黑白主题切换按钮
        $('#all_style').on('click', function () {
            if ($("body").attr("class") == 'light') {
                $("body").removeClass();
                $("body").addClass("dark");
                $("#all_style i").html('&#xe6c2;');
            } else {
                $("body").removeClass();
                $("body").addClass("light");
                $("#all_style i").html('&#xe748;');
            }
        });

        // 发送消息小飞机按钮和文字按钮
        $("#goplane, #go").click(function () {
            gogogo($('#question').val());
        });

        $("#recordingbutton").on("touchstart mousedown", function (e) {
            if (isrecordersuccess) {
                startRecording();
                recordingtip = layer.msg('<div style="display: flex;align-items:center;"><img src="/static/ai/img/voice.gif" style="margin-right:20px;width:50px;">松开 发送</div>', { time: false });
            } else {
                layer.msg('找不到录音设备');
            }
            e.preventDefault(); // 取消事件的默认动作
        });

        $("#recordingbutton").on("touchend mouseup", function (e) {
            if (isrecordersuccess) {
                layer.close(recordingtip);
                stopRecording();
            }
        });

        // 禁止弹出系统默认的右键功能
        $("#recordingbutton").on("contextmenu", function (e) {
            e.preventDefault();
        });
    });


    // 初始化
    $(function () {
        if ($.cookie("fullscreen")) {
            $('.home_container:first').toggleClass('home_tight-container');
            $('.max_min i').toggleClass('layui-icon-screen-restore');
        }

        if ($.cookie("entersetting") == 0) {
            $("#question").prop("placeholder", "Enter 发送，Shift + Enter换行");
        }
        $("#question").keydown(function (e) {
            if ($.cookie("entersetting") == 0) {
                if (!e.ctrlKey && !e.altKey && !e.shiftKey && e.which == 13) {
                    gogogo($('#question').val()); return false;
                }
            } else {
                if (e.ctrlKey && e.which == 13) {    //ctrl+enter键
                    gogogo($('#question').val()); return false;
                }
            }
        });

        // 监听输入框
        $('#question').bind('input propertychange', 'textarea', function () {
            var input = $('#question').val();

            // 打开预设场景
            if (input == '/') {
                $('.home_prompt-hints').show();
            } else {
                $('.home_prompt-hints').hide();
            }

            // 输入中元素是否存在
            if ($('.home_chat-message-status').length) {
                if (input == undefined || input == null || input == '' || input == ' ') {
                    $('.home_chat-message-user:last').remove();
                    return false;
                }
                $(".home_chat-message-user:last code").text(input);
            } else {
                user_output(input, true); //把textarea内容输出到对话框中并提示正在输入
                chat_roll();
            }
        });

        $.removeCookie('check_id');
        if ($.cookie('userrndstr')) {
            userrndstr = decryptCookie($.cookie('userrndstr'), 'ChatGPT');
            checkuserstatus();
        } else {
            userrndstr = randomString(8) + websiterndstr;
        }

        // 角色、回车配置下拉框
        var role_js = $.parseJSON($('#role_js').val());
        var enter_js = $.parseJSON('[{"title":"按 Enter 键发送","value":0},{"title":"按 Ctrl+Enter 键发送","value":1}]');

        layui.use('dropdown', function () {
            var dropdown = layui.dropdown, $ = layui.jquery;
            dropdown.render({
                elem: '.role_js',
                style: 'background-color: var(--white);',
                className: 'dropdown-background',
                data: role_js,
                click: function (data, othis) {
                    $('#role').val(data.rolevalue);
                    $(this.elem).children('span').text(data.title);
                    $.cookie('role', data.rolevalue);
                    $.cookie('rolename', data.title);
                }
            });
        });

        layui.use('dropdown', function () {
            var dropdown = layui.dropdown, $ = layui.jquery;
            dropdown.render({
                elem: '.enter_js',
                style: 'background-color: var(--white);',
                className: 'dropdown-background',
                data: enter_js,
                click: function (data, othis) {
                    entersetting(data.value);
                    $.cookie('entersetting', data.value);
                }
            });
        });

        var clipboard = new ClipboardJS(".copy_msg,.copy_code,.copy_p", {
            text: function (trigger) {
                if ($(trigger).prop('nodeName') == 'DIV') {
                    return $(trigger).parent().next().text();
                } else if ($(trigger).prop('nodeName') == 'P') {
                    return $(trigger).text();
                } else if ($(trigger).prop('nodeName') == 'BUTTON') {
                    return $('.home_export-content').text();
                } else {
                    return $(trigger).parent().text();
                }
            },
        });
        clipboard.on('success', function (e) {
            layer.msg('复制成功', { skin: 'custom_alert1' });

        });
        clipboard.on('error', function (e) {
            layer.msg('复制失败！', { skin: 'custom_alert1' });
        });

        // 全局样式切换
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $("body").addClass("dark");
            $("#all_style i").html('&#xe6c2;');
        } else {
            $("body").addClass("light");
            $("#all_style i").html('&#xe748;');
        }

        clipboard.on('error', function (e) {
            layer.msg('复制失败！', { skin: 'custom_alert1' });
        });

        // 模型样式兼容手机
        if (navigator.userAgent.match(/mobile/i)) {
            $('.pc_div').remove();
        } else {
            $('.tabs').remove();
        }

        // 模型选中状态
        if ($.cookie('modelname')) {
            $('.mode_js').children('span').text($.cookie('modelname'));
            $('#checked_' + $.cookie('model')).css("border-color", "var(--primary)");
        }
    });

    function checkloginstatus() {
        if (!user_uid) {
            //$('.home_sidebar').toggleClass('home_sidebar-show');
            showqrcode();
            return false;
        }
        return true;
    }

    // 设置模型
    function set_model(modelid, modelvalue, modelisimage, title, modelprice) {
        $('#checked_' + $.cookie('model') + '_' + $.cookie('modelisimage')).css("border-color", "");

        $('#model').val(modelid);
        $('#modelvalue').val(modelvalue);
        $('#modelisimage').val(modelisimage);
        $('.mode_js').children('span').text(title);
        $('#checked_' + modelid + '_' + modelisimage).css("border-color", "var(--primary)");
        $.cookie('model', modelid);
        $.cookie('modelvalue', modelvalue);
        $.cookie('modelisimage', modelisimage);
        $.cookie('modelname', title);
        $.cookie('modelprice', modelprice);
    }

    // 新建会话
    function create_chat() {
        enableModelContent();
        if ($(".home_chat-item-title:first").text() == "新的聊天") {
            $(".home_chat-item:first").click();
        } else if (user_uid) {
            lastpicurl = "";
            revised_prompt = "";
            contextarray = [];
            conversationid = Date.now();
            chat_prepend(conversationid, '新的聊天');
            $.cookie('check_id', conversationid);
            showconversation(conversationid);
        }
    }

    // 重试消息
    function retry_msg(obj) {
        var prev_msg = $(obj).parents().filter(".home_chat-message").prev().find("code").text();
        $('#question').val(prev_msg);
        user_output(prev_msg, true);
        gogogo(prev_msg);
    }

    function show_katex(obj) {
        var newobj = $(obj).parents().filter(".home_chat-message").find(".markdown-body");
        var texhtml = newobj.html();
        const txt = document.createElement("textarea");
        txt.innerHTML = texhtml;
        var texhtml = txt.value; //由于katex无法正确处理html对<和>等字符的编码&lt;和&gt;，因此需要通过textarea做下转码。
        var inlineRegex = /\$\s*(.+?)\s*\$/g;
        var displayRegex = /\$\$\s*(.+?)\s*\$\$/g;
        var inlineBracketsRegex = /\\\(\s*(.+?)\s*\\\)/g;
        var displayBracketsRegex = /\\\[\s*(.+?)\s*\\\]/g;
        texhtml = texhtml.replace(displayRegex, function (match, tex) {
            try {
                return katex.renderToString(tex, {
                    throwOnError: false,
                    displayMode: true
                });
            } catch (e) {
                console.error('KaTeX auto-render: Failed to parse `' + tex + '` with ', e);
                return match;
            }
        });
        texhtml = texhtml.replace(displayBracketsRegex, function (match, tex) {
            try {
                return katex.renderToString(tex, {
                    throwOnError: false,
                    displayMode: true
                });
            } catch (e) {
                console.error('KaTeX auto-render: Failed to parse `' + tex + '` with ', e);
                return match;
            }
        });
        texhtml = texhtml.replace(inlineRegex, function (match, tex) {
            try {
                return katex.renderToString(tex, {
                    throwOnError: false,
                    displayMode: false
                });
            } catch (e) {
                console.error('KaTeX auto-render: Failed to parse `' + tex + '` with ', e);
                return match;
            }
        });
        texhtml = texhtml.replace(inlineBracketsRegex, function (match, tex) {
            try {
                return katex.renderToString(tex, {
                    throwOnError: false,
                    displayMode: false
                });
            } catch (e) {
                console.error('KaTeX auto-render: Failed to parse `' + tex + '` with ', e);
                return match;
            }
        });
        newobj.html(texhtml);
    }

    function show_mathjax(obj) { //另外一种解析公式的方案，需要的话请把页面内的show_katex修改为这个函数，index.php页面需要取消加载mathjax.js的相关注释
        MathJax.Hub.Config({
            showProcessingMessages: false,
            messageStyle: "none",
            extensions: ["tex2jax.js"],
            jax: ["input/TeX", "output/HTML-CSS"],
            tex2jax: {
                inlineMath: [["$", "$"], ["\\(", "\\)"]],
                displayMath: [["$$", "$$"], ["\\[", "\\]"]],
                skipTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code', 'a'],
                ignoreClass: "comment-content"
            },
            "HTML-CSS": {
                availableFonts: ["STIX", "TeX"],
                showMathMenu: false
            },
            showProcessingMessages: false,    //隐藏加载时候左下角加载情况的展示
            messageStyle: "none"              //隐藏加载时候左下角加载情况的展示
        });
        MathJax.Hub.Queue(["Typeset", MathJax.Hub], obj);
    }

    function read_msg(obj) {
        var newobj = $(obj).parents().filter(".home_chat-message");
        var objid = newobj.find(".markdown-body").prop("id");
        var answer_msg = newobj.find(".markdown-body").text();
        if ($(obj).text() == "暂停朗读") { //用户想暂停当前正在朗读的文本
            stopAllAudio();
            return;
        }
        stopAllAudio(); //尝试暂停其他正在朗读的文本

        for (var i = 0; i < audioarray.length; i++) {
            if (audioarray[i][0] === objid) {   //曾经加载过的文本音频
                if ($(obj).text() == "开始朗读") {
                    layer.msg('开始朗读', { skin: 'custom_alert1' });
                } else {
                    layer.msg('继续朗读', { skin: 'custom_alert1' });
                }
                audioarray[i][1].play();
                $(obj).text("暂停朗读");
                nowReadingId = objid;
                return;
            }
        }
        layer.msg('<span style="margin:0 10px;">加载中，稍后将自动朗读</span>', { skin: 'custom_alert1' }); //第一次朗读文本
        var newsound = new Howl({
            src: ["/ai/tts/get_tts?userrndstr=" + userrndstr + "&msg=" + encodeURIComponent(answer_msg)],
            html5: true,
            pool: 10,
            preload: 'metadata',
            format: ['mp3'],
            onend: endreading
        });
        newsound.play();
        nowReadingId = objid;
        $(obj).text("暂停朗读");
        audioarray.push([objid, newsound]);
        //var playInterval = setInterval(function () {
        //    var lastAudio = audioarray[audioarray.length - 1][1];
        //    if (!lastAudio.playing()) {
        //        lastAudio.play();
        //    } else {
        //        clearInterval(playInterval);
        //    }
        //}, 200);
    }

    function endreading() {
        $("#" + nowReadingId).prev('div').find('div:contains("暂停朗读")').text("朗读文本");
        nowReadingId = 0;
    }

    function user_output(msg, input = false, ishtml = false) {
        var input_h = (input) ? '<div class="home_chat-message-status">正在输入…</div>' : '';
        var html = '<div class="home_chat-message-user">' +
            '<div class="home_chat-message-container">' + input_h +
            '<div class="home_chat-message-item">' +
            '<div class="markdown-body">' +
            '<div class="home_chat-message-top-actions" style="left:0;">' +
            '<div class="home_chat-message-top-action copy_msg" data-clipboard-action="copy">复制</div>' +
            '</div>' +
            '<code style="background:transparent;font-size:100%;padding:0;"></code>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        $('.home_chat-body').append(html);
        if (ishtml) {
            $(".home_chat-message-user:last code").html(parseMarkdownImage(msg));
        } else {
            $(".home_chat-message-user:last code").text(msg);
        }
    }

    function ai_output(id, time, model, msg = '') {
        var html = '<div class="home_chat-message">' +
            '<div class="home_chat-message-container">' +
            '<div class="home_chat-message-item">' +
            '<div class="home_chat-message-top-actions">' +
            '<div class="home_chat-message-top-action" onclick="retry_msg(this)">重试</div>' +
            '<div class="home_chat-message-top-action copy_msg" data-clipboard-action="copy">复制</div>' +
            '</div>' +
            '<div class="markdown-body" id="' + id + '">' + msg + '</div>' +
            '</div>' +
            '<div class="home_chat-message-actions">' +
            '<div class="home_chat-message-action-date"><span style="padding:2px 5px;margin-left:5px;color:#4f80e1;background:#e7f8ff;border-radius:4px;">由AI生成</span></div><div class="home_chat-message-action-date">' + time + '</div>' +
            '<div class="home_chat-message-action-date" style="margin-right:10px;">' + model + '模型</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        $('.home_chat-body').append(html);
    }

    function chat_prepend(id, name, num = 0, time = getMyDate()) {
        var html = '<div id="' + id + '" class="home_chat-item home_chat-item-selected" onclick="showconversation(' + id + ');" oncontextmenu="event.preventDefault();changeconversationtitle(' + id + ')">' +
            '<div class="home_chat-item-title">' + parseMarkdownImage(name, true) + '</div>' +
            '<div class="home_chat-item-info">' +
            '<div class="home_chat-item-count">' + num + ' 条对话</div>' +
            '<div class="home_chat-item-date">' + time + '</div>' +
            '</div>' +
            '<i onclick="deleteconversation(' + id + ')" class="layui-icon home_chat-item-delete">&#xe693;</i>' +
            '</div>';

        $('.home_sidebar-body').children().removeClass("home_chat-item-selected");
        $('.home_sidebar-body').prepend(html);
    }

    function chat_append(id, name, num = 0, time = getMyDate()) {
        var html = '<div id="' + id + '" class="home_chat-item home_chat-item-selected" onclick="showconversation(' + id + ');" oncontextmenu="event.preventDefault();changeconversationtitle(' + id + ')">' +
            '<div class="home_chat-item-title">' + parseMarkdownImage(name, true) + '</div>' +
            '<div class="home_chat-item-info">' +
            '<div class="home_chat-item-count">' + num + ' 条对话</div>' +
            '<div class="home_chat-item-date">' + time + '</div>' +
            '</div>' +
            '<i onclick="deleteconversation(' + id + ')" class="layui-icon home_chat-item-delete">&#xe693;</i>' +
            '</div>';

        $('.home_sidebar-body').children().removeClass("home_chat-item-selected");
        $('.home_sidebar-body').append(html);
    }

    // 会话页面滚动到底部
    function chat_roll(type = 'low') {
        var low = $('.home_chat-body').prop("scrollHeight");
        $('.home_chat-body').scrollTop(low);
    }

    // 获取当前时间
    function getMyDate(time = false) {
        if (time == true) return Date.now() + String(new Date().getMilliseconds());
        let now = new Date,
            y = now.getFullYear(),
            m = now.getMonth() + 1,
            d = now.getDate();
        return y + "-" + (m < 10 ? "0" + m : m) + "-" + (d < 10 ? "0" + d : d) + " " + now.toTimeString().substring(0, 8);
    }

    // 记录消息
    function messageLog() {
        // 更新页面数量
        var msg_num = Number($('#chat_msg_count').text()) + 1;
        $('#' + $.cookie('check_id') + ' .home_chat-item-count').text(msg_num + '条消息');
        $('#chat_msg_count').text(msg_num);
    }

    // 打开登录框
    function showqrcode() {
        if (iswindowloginonly) {
            showuserlogin();
            layerqrcode = layer.open({
                type: 1,
                title: false,
                closeBtn: false,
                area: ['300px', '45%'],
                content: $('#mydialog'),
                shadeClose: true,
            });
            checkuserstatus();
        } else if (isthirdpartyloginonly) {
            window.open(thirdpartyloginurl, '_blank');
        } else {
            if (isWechat) {
                //console.log('index.showqrcode')
                $.cookie('userrndstr', encryptCookie(userrndstr, 'ChatGPT'), {
                    expires: 30,
                    path: '/'
                });
                layer.msg("登录中，请稍候……", {
                    icon: 16,
                    shade: 0.4,
                    time: false //取消自动关闭
                });
                location.href = window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/s.php?m=1&s=" + userrndstr;
            } else if (isMobile) {
                layerqrcode = layer.msg("跳转到微信小程序登录中……", {
                    icon: 16,
                    shade: 0.4,
                    time: false
                });
                if (isAndroidApp) {
                    $.get("wxapplet.php?from=android&s=" + userrndstr, function (data) {
                        sso.wxapplet(data);
                        checkuserstatus();
                    }).fail(function () {
                        layer.close(layerqrcode);
                        layer.msg("登录失败，请重试", {
                            icon: 16,
                            shade: 0.4,
                            time: 1
                        });
                    });
                } else {
                    if (/iPhone/i.test(navigator.userAgent)) {
                        location.href = window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/wxapplet.php?from=mobile&s=" + userrndstr;
                    } else {
                        if ((/Quark/i.test(navigator.userAgent)) || (/EdgA/i.test(navigator.userAgent))) {
                            location.href = window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/wxapplet.php?from=mobile&s=" + userrndstr;
                        } else {
                            $("#temp").attr("src", (window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/wxapplet.php?from=mobile&s=" + userrndstr));
                        }
                    }
                    checkuserstatus();
                }
            } else {
                $("#mycontent").html("");
                $("#mycontent").qrcode(window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/s.php?s=" + userrndstr);
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

    // 微信扫码登录
    function showwxlogin() {
        $("#mytitle").html(logintitleleft);
        $("#mycontent").html("");
        $("#mycontent").qrcode(window.location.protocol + "//" + window.location.hostname + ":" + window.location.port + "/s.php?s=" + userrndstr);
    }

    // 邮箱密码登录
    function showuserlogin() {
        $("#mytitle").html(logintitleright);
        $("#mycontent").html("<iframe style='width:100%;height:100%;' src='/ai/index/user_login?userrndstr=" + userrndstr + "' scrolling='no'></iframe>");
    }
    // 检查登录状态
    function checkuserstatus() {
        $.ajax({
            url: "/member/login/check_user?rid=" + randomString(10) + "&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    user_uid = data.uid;
                    user_email = data.email;
                    $(".home_sidebar-header-bar").html('<button onclick="showuserinfo()" class="button_icon-button button_shadow home_sidebar-bar-button clickable"><div class="button_icon-button-icon"><i class="layui-icon">&#xe66f;</i></div><div class="button_icon-button-text">个人信息</div></button>');
                    $(".home_sidebar-header-bar").append('<button onclick="buy()" class="button_icon-button button_shadow home_sidebar-bar-button clickable"><div class="button_icon-button-icon"><i class="layui-icon">&#xe65e;</i></div><div class="button_icon-button-text">充值</div></button>');
                    $(".home_sidebar-header-bar").append('<button onclick="logout()" class="button_icon-button button_shadow home_sidebar-bar-button clickable"><div class="button_icon-button-icon"><i class="layui-icon">&#xe682;</i></div><div class="button_icon-button-text">退出</div></button>');
                    layer.close(layerqrcode);
                    if (data.email == "") setTimeout("showuserinfo();", 500);
                    // 设置cookie
                    $.cookie('userrndstr', encryptCookie(userrndstr, 'ChatGPT'), {
                        expires: 30,
                        path: '/'
                    });
                    refreshquota();
                    refreshhistory();
                    $("#userpanel").show();
                    $("#question").removeAttr("disabled");
                    $("#question").val("");
                    $("#divsharechat").show();
                } else {
                    if ($.cookie('userrndstr')) {
                        $.removeCookie('userrndstr', { path: '/' });
                        layer.alert('[登录]您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                            icon: 2,
                            btn: ['确定'],
                            closeBtn: 0,
                            yes: function () {
                                location.reload();
                            }
                        });
                    } else {
                        setTimeout(checkuserstatus, 3000); // 增加重试间隔
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Check User Status Error:', error);
                setTimeout(checkuserstatus, 3000); // 增加重试间隔
            }
        });
    }

    // 退出
    function logout() {
        $.ajax({
            url: "/ai/index/logout",
            type: "POST",
            data: {
                userrndstr: userrndstr,
                check_id: $.cookie('check_id')
            },
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    layer.msg(data.message);
                    $.removeCookie('userrndstr', { path: '/' });
                    $.removeCookie('check_id');
                    location.reload();
                }
            },
            error: function (xhr, status, error) {
                console.error('Logout Error:', error);
                layer.msg('退出失败，请重试');
            }
        });
    }

    // 获取随机字符串
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

    // 刷新用户信息
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
                console.error('Refresh Quota Error:', error);
                setTimeout(refreshquota, 3000); // 增加重试间隔
            }
        });
    }

    // 个人信息
    function showuserinfo() {
        if ($("#userinfoframe").length == 0) {
            $("#mytitle").html('<div style="display: flex;"><div style="width: 100%; height: 50px;background-color: rgb(79, 128, 225);color: #fff;font-size: 16px;line-height: 50px;text-align: center;">用户信息维护' + ((isWechat) ? '' : '及绑定') + '</div></div>');
            $("#mycontent").html("<iframe style='width:100%;height:100%;' id='userinfoframe' src='/ai/personal/user_info?userrndstr=" + userrndstr + "' scrolling='no'></iframe>");
        }
        let mydialog = $('#mydialog').html();
        layer.open({
            type: 1,
            title: false,
            closeBtn: false,
            area: ['300px', '45%'],
            content: mydialog,
            shadeClose: true,
            id: 'userinfo',
        });
    }

    // 发送
    function gogogo(msg) {
        //if (msg == '' || msg == ' ' || msg == null || msg == undefined) return false;

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
        $("#imageurl").val(""); //清空之前已上传图片的url，防止干扰
        $("#blendimageurl").val("");
        if (!buttongo) {
            if ($("#modelisimage").val() == "0") {
                userforcestop = true;
                buttongo = true;
                if (!isalltext) {
                    contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                    contextarray = contextarray.slice(-10);
                    es.close();
                }
            }
            $("#question").val("");
            $("#question").attr("disabled", false);
            $("#go").text(" 发送 ");
            if (!isMobile) $("#question").focus();
            refreshquota();
            freezegogogo = false;
        } else {
            if ($('#preview').length) {             //如果用户已上传图片，则检查模型是否匹配
                if ($("#modelisimage").val() <= 1) {
                    setTimeout("$('#model_list_div').show();", 100);
                    layer.msg("模型类型不匹配，请选择合适的模型", { icon: 5 });
                    freezegogogo = false;
                    return;
                }
            }
            prompt = msg;
            if (prompt == "") {
                if ($("#modelisimage").val() == 4) { //识图可以不输入文字(Bard识图必须有文字，在setsession中处理了)
                    previewResizeAndUpload();
                } else if ($("#modelisimage").val() == 2) { //改图
                    if ($("#modelvalue").val() == "midjourney_image") {
                        if ($('#blendpreview').length) {
                            previewResizeAndUpload(true, true);
                        } else {
                            layer.msg("使用MidJourney模型修改图片必须输入提示词", { icon: 5 });
                            freezegogogo = false;
                            return;
                        }
                    }
                    previewResizeAndUpload(true);
                } else {
                    layer.msg("请输入您的问题", { icon: 5 });
                }
                freezegogogo = false;
                return;
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
                                $(".home_chat-message-user:last code").text(decodeURIComponent(data.newtext));
                                prompt = decodeURIComponent(data.newtext);
                                preparetoask();
                            }
                        },
                        error: function (xhr, status, error) {
                            layer.msg("您录入的问题触发了防SQL注入检测");
                            return;
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
                timeout: 5000, // 设置超时时间为5秒
                success: function (data) {
                    layer.close(loading);
                    loading = 0;
                    if (!data.isvalid) {
                        layer.msg("您录入的问题未通过内容审核");
                        return;
                    } else {
                        if ($("#modelisimage").val() == 4) { //识图
                            previewResizeAndUpload();
                        } else if ($("#modelisimage").val() == 2) { //改图
                            if ($('#blendpreview').length) {
                                previewResizeAndUpload(true, true);
                            }
                            previewResizeAndUpload(true);
                        } else {
                            starttoask();
                        }
                    }
                },
                error: function () {
                    layer.close(loading);
                    loading = 0;
                }
            });
        } else {
            if ($("#modelisimage").val() == 4) { //识图
                previewResizeAndUpload();
            } else if ($("#modelisimage").val() == 2) { //改图
                if ($('#blendpreview').length) {
                    previewResizeAndUpload(true, true);
                }
                previewResizeAndUpload(true);
            } else {
                starttoask();
            }
        }
    }

    function starttoask() {

        // 取消发送中状态
        $('.home_chat-message-status').remove();
        messageLog();
        // 发送内容写入聊天界面
        buttongo = false;
        answer = randomString(16);

        if (($("#modelisimage").val() == "1") || ($("#modelisimage").val() == "2")) {
            tips = "AI正在生成图片中……";
        } else {
            tips = "AI正在思考……";
        }
        ai_output(answer, getMyDate(), $(".mode_js span").text(), tips);
        retrytimes = 0;
        send_post();
    }


    function timer() {
        if (previousScrollPosition > $('.home_chat-body').prop("scrollTop")) {
            userScrolling = true;
        } else if ($('.home_chat-body').prop("scrollTop") == $('.home_chat-body').prop("scrollHeight")) {
            userScrolling = false;
        }
        let newalltext = alltext;
        //有时服务器错误地返回\\n作为换行符，尤其是包含上下文的提问时，这行代码可以处理一下。
        if (newalltext.split("\n").length == 1) {
            newalltext = newalltext.replace(/\\n/g, '\n');
        }
        if (newalltext.endsWith('//')) {
            var newalltextlen = newalltext.length - 2;
        } else if (newalltext.endsWith('/')) {
            var newalltextlen = newalltext.length - 1;
        } else {
            var newalltextlen = newalltext.length;
        }
        //用newalltext.length - 3是因为如果服务端返回\\n或\n时拆成两条消息了，str_会把单个\当成字符。
        if (str_.length < newalltextlen) {
            str_ += newalltext[i++];
            strforcode = str_;
            if ((str_.split("```").length % 2) == 0) {
                strforcode += "\n```\n";
            } else {
                strforcode += "_";
            }
            if ((newalltextlen - str_.length) > 10) {
                timewait = 20;
            }
            if ((newalltextlen - str_.length) > 20) {
                timewait = 10;
            }
            if ((newalltextlen - str_.length) > 30) {
                timewait = 5;
            }
            if ((newalltextlen - str_.length) > 50) {
                str_ = newalltext.slice(0, -50);
                i = str_.length;
            }
            if (!userforcestop) {
                setTimeout(timer, timewait);
            } else {
                userforcestop = false;
            }
        } else {
            if (isalltext) {
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
                        timeout: 5000, // 设置超时时间为5秒
                        success: function (data) {
                            layer.close(loading);
                            loading = 0;
                            if (layerconfirm) {
                                layer.close(layerconfirm);
                            }
                            if (!data.isvalid) {
                                strforcode = "AI的回答未通过内容审核！";
                                renderhtml(strforcode);
                            }
                        },
                        error: function () {
                            layer.close(loading);
                            loading = 0;
                            if (layerconfirm) {
                                layer.close(layerconfirm);
                            }
                        }
                    });
                }
                renderhtml(strforcode);

                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                refreshquota();
                messageLog();
                changeConversationName();

                if ($('#' + $.cookie('check_id') + ' .home_chat-item-title').text() == '新的聊天') {
                    $('.home_export-content').html('###' + htmlspecialchars(prompt));
                }
                mathjaxstr = (containsFormula(strforcode)) ? '<div class="home_chat-message-top-action" onclick="show_katex(this)">显示公式</div>' : '';
                readmsg = '<div class="home_chat-message-top-action" onclick="read_msg(this)">朗读文本</div>';
                $("#" + answer).prev().html(mathjaxstr + $("#" + answer).prev().html() + readmsg);
                $('.home_export-content').append("\r\n## 来自 你 的消息:\r\n" + htmlspecialchars(prompt));
                $('.home_export-content').append("\r\n## 来自 AI 的消息:\r\n" + mdHtml.render(escapeMarkdownBackslashes(strforcode)));
                if (isasrquestion) $("#" + answer).prev('div').find('div:contains("朗读文本")').click();
            } else {
                if (!userforcestop) {
                    setTimeout(timer, timewait);
                } else {
                    userforcestop = false;
                }
            }
        }
        renderhtml(strforcode);
        previousScrollPosition = $('.home_chat-body').prop("scrollTop");
    }

    // MJ画图
    function mjdraw(isdrawuv = 0, isblend = 0) {
        $.get("/plugins/mj/getmjpicture.php?isdrawuv=" + isdrawuv + "&isblend=" + isblend + "&user=" + user_uid + "&rid=" + randomString(10), function (data) {
            try {
                layer.close(loading);
                loading = 0;
                if (layerconfirm) {
                    layer.close(layerconfirm);
                }
                if (data.error) {
                    var errcode = data.error.code;
                    var errmsg = data.error.message;

                    errmsg = get_error_msg(errcode, errmsg);
                    if ((errcode == "model_not_found") || (errcode == "invalid_user") || (errcode == "out_of_money") || (errcode == "mj_fail") || (errcode == "no_valid_apikey") || (errcode == "context_length_exceeded")) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == null) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == "unknown") {
                        $("#" + answer).html(decodeURIComponent(errmsg));
                    } else {
                        if (retrytimes < 3) {
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
                    if (!isMobile) $("#question").focus();
                    messageLog();
                    chat_roll();
                    return;
                } else {
                    if (isdrawuv == 1) { //这里处理的是放大图，即UPSCALE操作生成的单图
                        //$("#" + answer).html("<img onload='chat_roll();showpic();' src='/index.php/ai/index/picture_proxy?url=" + encodeURIComponent(data.data[0].url) + "'>");
                        $("#" + answer).html("<img onload='chat_roll();showpic();' src='" + data.data[0].url + "'>");
                        $("#" + answer).prev('div').find('div:contains("复制")').hide();
                        buttongo = true;
                        $("#question").val("");
                        $("#question").attr("disabled", false);
                        $("#go").text(" 发送 ");
                        if (!isMobile) $("#question").focus();
                        refreshquota();
                        messageLog();
                        changeConversationName();
                    } else { //下面处理的是MJ四格图的第一个预览，还是要用proxy加载图片
                        $("#" + answer).html("<img onload='chat_roll();' src='/ai/index/picture_proxy?url=" + encodeURIComponent(data.data[0].url) + "'>");
                        $("#question").val("图片正在生成中……");
                        refreshquota();
                        messageLog();
                        changeConversationName();
                        refreshmjpic(data.data[0].url);
                    }
                }
            } catch (error) {
                $("#" + answer).html("接口返回错误信息：" + data);
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                messageLog();
                chat_roll();
                return;
            }
        }, "json");
    }

    // 其他画图
    function draw() {
        $.get("/ai/picture/generate?user=" + userrndstr + "&isimage=" + $("#modelisimage").val(), function (data) {
            layer.close(loading);
            loading = 0;
            if (layerconfirm) {
                layer.close(layerconfirm);
            }
            if (data.error) {
                var errcode = data.error.code;
                var errmsg = data.error.message;

                errmsg = get_error_msg(errcode, errmsg);
                if ((errcode == "picture_not_allowed") || (errcode == "model_not_found") || (errcode == "invalid_user") || (errcode == "out_of_money") || (errcode == "no_valid_apikey") || (errcode == "context_length_exceeded")) {
                    $("#" + answer).html(errmsg);
                } else if (errcode == null) {
                    $("#" + answer).html(errmsg);
                } else if (errcode == "unknown") {
                    $("#" + answer).html(decodeURIComponent(errmsg));
                } else {
                    if (retrytimes < 3) {
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
                if (!isMobile) $("#question").focus();
                messageLog();
                chat_roll();
                return;
            } else {
                //$("#" + answer).html("<img onload='chat_roll();showpic();' src='/ai/index/picture_proxy?url=" + encodeURIComponent(data.data[0].url) + "'>");
                $("#" + answer).html("<img onload='chat_roll();showpic();' src='" + data.data[0].url + "'>");
                $("#" + answer).prev('div').find('div:contains("复制")').hide();
                lastpicurl = data.data[0].url;
                if (data.data[0].revised_prompt) {
                    revised_prompt = data.data[0].revised_prompt + ".请基于以上描述画图，并按以下要求进行修改：";
                }
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                refreshquota();
                messageLog();
                changeConversationName();
                // refreshhistory();
            }
        }, "json");
    }

    // 对话
    function streaming() {
        isstarted = true;
        isalltext = false;
        alltext = "";
        try {
            es.close(); //EventSource有时会间隔3秒自动重连，并且不触发error事件。这行代码可以结束上一次死循环的连接。
        } catch (e) { }
        es = new EventSource("/ai/stream/get_stream?user=" + userrndstr + "&isimage=" + $("#modelisimage").val());
        es.onerror = function (event) {
            layer.close(loading);
            loading = 0;
            if (layerconfirm) {
                layer.close(layerconfirm);
            }
            es.close();
            buttongo = true;
            $("#" + answer).html("服务器访问超时或网络错误");
            $("#question").val("");
            $("#question").attr("disabled", false);
            $("#go").text(" 发送 ");
            if (!isMobile) $("#question").focus();
            return false;
        }
        es.onmessage = function (event) {

            if (event.data == "[DONE]") {
                isalltext = true;
                contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                contextarray = contextarray.slice(-10); //保留最近10次对话作为上下文，并由setsession.php判断是否超过最大tokens限制
                es.close();
                return true;
            }
            try {
                json = JSON.parse(event.data);
                if (json.hasOwnProperty("error")) { //处理错误信息

                    layer.close(loading);
                    loading = 0;
                    if (layerconfirm) {
                        layer.close(layerconfirm);
                    }
                    var errcode = json.error.code;
                    var errmsg = json.error.message;

                    errmsg = get_error_msg(errcode, errmsg);
                    es.close();
                    if ((errcode == "model_not_found") || (errcode == "invalid_user") || (errcode == "out_of_money") || (errcode == "no_valid_apikey") || (errcode == "context_length_exceeded") || (errcode == "DataInspectionFailed")) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == null) {
                        $("#" + answer).html(errmsg);
                    } else if (errcode == "unknown") {
                        $("#" + answer).html(errmsg);
                    } else {
                        if (retrytimes < 3) {
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
                    if (!isMobile) $("#question").focus();
                    return true;
                } else if (((json.hasOwnProperty("choices")) && (json.choices == "")) || (((json.choices[0].hasOwnProperty("delta")) && (json.choices[0].delta.hasOwnProperty("content")) && ((json.choices[0].delta.content == "") || (json.choices[0].delta.content == null))))) { //处理微软openai azure返回的干扰消息
                    if ((json.choices[0].delta.hasOwnProperty("reasoning_content")) && (json.choices[0].delta.reasoning_content)) { //处理deepseek模型的思维链数据
                        if (reasoning_content == "") {
                            reasoning_content = json.choices[0].delta.reasoning_content.replace(/^\n+/, '');
                        } else {
                            reasoning_content += json.choices[0].delta.reasoning_content;
                        }
                    } else {
                        return true;
                    }
                } else if (json.choices[0].delta.hasOwnProperty("content")) { //处理正常流程的data
                    if (alltext == "") {
                        alltext = json.choices[0].delta.content.replace(/^\n+/, '');
                    } else {
                        alltext += json.choices[0].delta.content;
                    }
                } else if (json.choices[0].finish_reason == "stop") { //处理正常流程结束标志
                    isalltext = true;
                    contextarray.push([encodeURIComponent(prompt), encodeURIComponent(alltext)]);
                    contextarray = contextarray.slice(-10);
                    es.close();
                    return true;
                }

                if (isstarted) {
                    userScrolling = false;
                    isstarted = false;
                    layer.close(loading);
                    loading = 0;
                    if (layerconfirm) {
                        layer.close(layerconfirm);
                    }
                    str_ = '';
                    i = 0;
                    strforcode = '';

                    timer();

                }
            } catch (e) {
                layer.close(loading);
                loading = 0;
                if (layerconfirm) {
                    layer.close(layerconfirm);
                }
                isalltext = true;
                buttongo = true;
                es.close();
                $("#" + answer).html("异常错误：" + event.data);
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                return false;
            }
        }

        if (contextarray.length == 0) {
            conversationid = Date.now();
        }
    }

    // 发送消息
    function send_post() {
        reasoning_content = "";
        $("#question").val(tips);
        $("#question").attr("disabled", true);
        $("#go").text(" 中止 ");

        loading = layer.msg(tips, {
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
        if (conversationid == undefined || conversationid == null || conversationid == "") {
            conversationid = Date.now();
        }
        setTimeout("checkloading(" + loading + ");", 60000);

        chat_roll();
        $("#imageurl").val($("#imageurl").val() ? $("#imageurl").val() : lastpicurl);
        $.ajax({
            cache: true,
            type: "POST",
            url: "/ai/index/set_session",
            data: {
                message: encodeURIComponent(revised_prompt + prompt),
                model: $("#model").val(),
                role: $("#role").val(),
                isimage: $("#modelisimage").val(),
                imageurl: $("#imageurl").val(),
                blendimageurl: $("#blendimageurl").val(),
                context: JSON.stringify(contextarray),
                user: userrndstr,
                conversationid: conversationid,
            },
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    if ($("#modelvalue").val() == "midjourney_image") {
                        if ($("#modelisimage").val() == "4") {
                            streaming();
                        } else {
                            if ($('#blendpreview').length) {
                                mjdraw(0, 1); //用户上传了两张图，混图流程
                            } else {
                                mjdraw(); //文生图或图生图流程
                            }
                        }
                    } else if (($("#modelisimage").val() == "1") || ($("#modelisimage").val() == "2")) {
                        draw();
                    } else {
                        streaming();
                    }
                } else {
                    console.error('API Error:', data);
                    layer.close(loading);
                    layer.msg('请求失败，请重试');
                    buttongo = true;
                    $("#question").val("");
                    $("#question").attr("disabled", false);
                    $("#go").text(" 发送 ");
                    if (!isMobile) $("#question").focus();
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                layer.close(loading);
                layer.msg('网络错误，请检查网络连接后重试');
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
            }
        });
    }

    //全屏展示图片
    function showpic() {
        $(".markdown-body").on("click", "img", function () {
            var imageUrl = $(this).attr("src");
            $(".overlay img").attr("src", imageUrl);
            $(".overlay").fadeIn();
            $("body").css("overflow", "hidden");
        });

        $(".overlay").click(function () {
            $(".overlay").fadeOut();
            $("body").css("overflow", "auto");
        });
    }

    //处理MJ有预览图的流程，不断刷新直到得到四格图
    function refreshmjpic(lastpic) {
        $.get("/plugins/mj/apiproxy.php?user=" + user_uid + "&rid=" + randomString(10), function (data) {
            if (data.error) {
                var errcode = data.error.code;
                var errmsg = data.error.message;
                errmsg = get_error_msg(errcode, errmsg);
                $("#" + answer).html(errmsg);
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                messageLog();
                chat_roll();
                return;
            } else {
                if (!data.data[1]) {
                    if (lastpic != data.data[0].url) { //下面处理的是MJ四格图生成阶段的预览图，还是要用proxy加载图片
                        $("#" + answer).html("<img onload='chat_roll();' src='/ai/index/picture_proxy?url=" + encodeURIComponent(data.data[0].url) + "'>");
                    }
                    setTimeout("refreshmjpic('" + data.data[0].url + "');", 2000);
                } else {
                    $("#" + answer).html(mj4pichtml(data.data[0].url, data.data[1].url, data.data[2].url, data.data[3].url));
                    $("#" + answer).prev('div').find('div:contains("复制")').hide();
                    buttongo = true;
                    $("#question").val("");
                    $("#question").attr("disabled", false);
                    $("#go").text(" 发送 ");
                    if (!isMobile) $("#question").focus();
                }
            }
        }, "json");
    }

    //生成MJ四格图的html格式
    function mj4pichtml(pic1, pic2, pic3, pic4) {
        return "<table><tr><td style='padding:1px;'><img onload='chat_roll();showpic();' src='" + pic1 + "'><span style='color:darkblue;float:left;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,1);'>生成高清图</span><span style='color:darkblue;float:right;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,2);'>生成相似图</span></td><td style='padding:1px;'><img onload='chat_roll();' src='" + pic2 + "'><span style='color:darkblue;float:left;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,1);'>生成高清图</span><span style='color:darkblue;float:right;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,2);'>生成相似图</span></td></tr><tr><td style='padding:1px;'><img onload='chat_roll();' src='" + pic3 + "'><span style='color:darkblue;float:left;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,1);'>生成高清图</span><span style='color:darkblue;float:right;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,2);'>生成相似图</span></td><td style='padding:1px;'><img onload='chat_roll();' src='" + pic4 + "'><span style='color:darkblue;float:left;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,1);'>生成高清图</span><span style='color:darkblue;float:right;width:50%;text-align:center;' class='clickable' onclick='getmoremjpic(this,2);'>生成相似图</span></td></tr></table>";
    }

    //实现已生成的四格图的UV功能
    function getmoremjpic(elem, action) {
        if (!midjourneymodelid) {
            layer.msg("系统中未发现MidJourney模型");
            return;
        }
        var imgSrc = $(elem).parent().find('img').attr('src');
        var urlParams = new URLSearchParams(imgSrc.substring(imgSrc.indexOf('?')));
        var taskid = urlParams.get('taskid');
        var customid = urlParams.get('customid');
        var imgid = urlParams.get('id');
        if (freezegogogo) return; //防止短时间内连续点击“发送”按钮造成本函数多次运行而造成不停请求的情况
        freezegogogo = true;
        messageLog();
        buttongo = false;
        answer = randomString(16);
        tips = "AI正在生成图片中……";
        $("#question").val(tips);
        $("#question").attr("disabled", true);
        $("#go").text(" 中止 ");

        loading = layer.msg(tips, {
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
        if (conversationid == undefined || conversationid == null || conversationid == "") {
            conversationid = Date.now();
        }
        setTimeout("checkloading(" + loading + ");", 60000);
        if (action == 1) {
            prompt = "[图片交互]生成高清图";
            user_output("[图片交互]生成高清图"); //把textarea内容输出到对话框中
            ai_output(answer, getMyDate(), midjourneymodelname, tips);
            chat_roll();
            $.ajax({
                cache: true,
                type: "POST",
                url: "/ai/index/set_session",
                data: {
                    message: encodeURIComponent(prompt),
                    model: midjourneymodelid,
                    role: "",
                    isimage: "2",
                    imageurl: "",
                    context: "",
                    user: userrndstr,
                    conversationid: conversationid,
                    midjourneyaction: "MJ::JOB::upsample." + imgid + "." + taskid + "." + customid
                },
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        mjdraw(1);
                    } else {
                        $.removeCookie('userrndstr', { path: '/' });
                        layer.alert('[问答]您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                            icon: 2,
                            btn: ['确定'],
                            closeBtn: 0,
                            yes: function () {
                                location.reload();
                            }
                        });
                    }
                }
            });
        } else if (action == 2) {
            prompt = "[图片交互]生成相似图";
            user_output("[图片交互]生成相似图"); //把textarea内容输出到对话框中
            ai_output(answer, getMyDate(), midjourneymodelname, tips);
            chat_roll();
            $.ajax({
                cache: true,
                type: "POST",
                url: "/ai/index/set_session",
                data: {
                    message: encodeURIComponent(prompt),
                    model: midjourneymodelid,
                    role: "",
                    isimage: "2",
                    imageurl: "",
                    context: "",
                    user: userrndstr,
                    conversationid: conversationid,
                    midjourneyaction: "MJ::JOB::variation." + imgid + "." + taskid + "." + customid
                },
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        mjdraw(2);
                    } else {
                        $.removeCookie('userrndstr', { path: '/' });
                        layer.alert('[问答]您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                            icon: 2,
                            btn: ['确定'],
                            closeBtn: 0,
                            yes: function () {
                                location.reload();
                            }
                        });
                    }
                }
            });
        }
        freezegogogo = false;
    }

    //刷新历史记录
    function refreshhistory(more = 0) {
        $.ajax({
            url: "/ai/personal/get_user_conversation?type=all&userrndstr=" + userrndstr + "&more=" + more,
            dataType: "json",
            async: false,
            success: function (data) {
                if (data.success) {
                    if (data.conversation) {
                        if (more == 0) {
                            $('.home_sidebar-body').html('');
                        }
                        $.each(data.conversation, function (k, v) {
                            if (v.id == $.cookie('check_id')) check_chat_exist = true;
                            chat_append(v.id, v.title, v.num, v.realtime);
                        })
                        if (more == 0) {
                            if (($.cookie('check_id') == undefined || check_chat_exist == false) && data.conversation.length > 0) $.cookie('check_id', (data.conversation[0].id));
                            showconversation($.cookie('check_id'));
                        } else {
                            $('#' + $.cookie('check_id')).addClass('home_chat-item-selected').siblings().removeClass("home_chat-item-selected");
                        }
                        nextrefreshhistorytime = more + 1;
                    } else {
                        if (more == 0) {
                            create_chat();
                        } else {
                            $('.home_sidebar-body').append('<div style="color:red;text-align:center;" class="home_chat-item home_chat-item-selected" onclick="deleteallconversation();">删除所有对话记录</div>');
                            $('#' + $.cookie('check_id')).addClass('home_chat-item-selected').siblings().removeClass("home_chat-item-selected");
                        }
                    }
                } else {
                    $.removeCookie('userrndstr', { path: '/' });
                    layer.alert('[历史对话]您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                        icon: 2,
                        btn: ['确定'],
                        closeBtn: 0,
                        yes: function () {
                            location.reload();
                        }
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Refresh History Error:', error);
            }
        });
    }

    //删除某个对话
    function deleteconversation(itemId) {
        event.stopPropagation();//禁止父级事件
        if ($('.home_sidebar-body').children().length <= 1) return layer.msg('至少保留一个对话');
        layer.confirm('确定要删除吗？', {
            btn: ['确定', '取消'],
            icon: 3,
            title: '确认删除',
        }, function (index) {
            layer.close(index);
            $.ajax({
                url: "/ai/personal/del_user_conversation?type=single&conversationid=" + itemId + "&userrndstr=" + userrndstr,
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        layer.msg(data.message);
                        //更新元素
                        var check_id = $('#' + itemId).siblings(":first").attr('id');//异常，不生效，立即被覆盖
                        // showconversation($('#'+chat_id).siblings(":first"));
                        $('#' + itemId).siblings(":first").addClass('home_chat-item-selected');
                        $('#' + itemId).remove();

                        $.cookie('check_id', check_id);
                        conversationid = check_id;
                        showconversation(check_id);
                        // refreshhistory();
                    } else {
                        layer.msg(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Delete Conversation Error:', error);
                    layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
                }
            });
        });
    }

    //删除全部对话
    function deleteallconversation() {
        event.stopPropagation();//禁止父级事件
        if ($('.home_sidebar-body').children().length <= 1) return false;

        layer.confirm('确定要删除所有对话记录吗？', {
            btn: ['确定', '取消'],
            icon: 3,
            title: '确认删除',
        }, function (index) {
            layer.close(index);
            $.ajax({
                url: "/ai/personal/del_user_conversation?type=all&userrndstr=" + userrndstr,
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        layer.msg(data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Delete All Conversation Error:', error);
                    layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
                }
            });
        });
    }

    //展示对话
    function showconversation(id) {
        if (!buttongo) return;
        $.cookie('check_id', id);
        if ($.cookie('model')) $('#model').val($.cookie('model'));
        if ($.cookie('modelvalue')) $('#modelvalue').val($.cookie('modelvalue'));
        if ($.cookie('modelisimage')) $('#modelisimage').val($.cookie('modelisimage'));
        if ($.cookie('modelname')) $('.mode_js').children('span').text($.cookie('modelname'));
        if ($.cookie('role')) $('#role').val($.cookie('role'));
        if ($.cookie('rolename')) $('.role_js').children('span').text($.cookie('rolename'));

        $('#' + id).addClass('home_chat-item-selected').siblings().removeClass("home_chat-item-selected");
        $('.home_sidebar').removeClass('home_sidebar-show');

        $.ajax({
            url: "/ai/personal/get_user_conversation?type=single&conversationid=" + id + "&userrndstr=" + userrndstr,
            dataType: "json",
            success: function (data) {
                if (data.success) {
                    let conversationname = '新的聊天';
                    if (data.conversation.length) {
                        conversationname = parseMarkdownImage(data.conversation[0].title, true);
                    }
                    $('.window-header-main-title').html(conversationname);
                    $('.home_export-content').html('###' + conversationname);
                    $('.home_chat-body').html('');
                    if (!data.conversation.length) {
                        showwelcomemessage();
                    }
                    $('#chat_msg_count').html(data.conversation.length * 2);
                    conversationid = id;
                    contextarray = [];

                    $.each(data.conversation, function (k, v) {
                        lastpicurl = "";
                        revised_prompt = "";
                        reasoning_content = "";
                        answer = randomString(16);
                        user_output(v.question, false, true);
                        ai_output(answer, v.realtime, v.model);
                        $('.home_export-content').append("\r\n## 来自 你 的消息:\r\n" + v.question);
                        $('.home_export-content').append("\r\n## 来自 AI 的消息:\r\n" + v.answer);

                        var pattern1 = /^!\[IMG\]\(.*&id=1\)\n!\[IMG\]\(.*&id=2\)\n!\[IMG\]\(.*&id=3\)\n!\[IMG\]\(.*&id=4\)$/;
                        var pattern2 = /^!\[IMG\]\(.*\)$/;
                        var pattern3 = /^!\[IMG\]\(.*\)\ndall-e-3官方修订提示词：.*$/;

                        if (pattern1.test(v.answer)) { //MidJourney生成的4张图片
                            let mjpic = (v.answer + "\n").replace(/\!\[IMG\]\(/g, "").split(")\n");
                            $("#" + answer).html(mj4pichtml(mjpic[0], mjpic[1], mjpic[2], mjpic[3]));
                            $("#" + answer).prev('div').find('div:contains("复制")').hide();
                        } else if (pattern2.test(v.answer)) { //其他画图模型生成的单张图片
                            let picarr = (v.answer + "\n").replace(/\!\[IMG\]\(/g, "").split(")\n");
                            $("#" + answer).prev('div').find('div:contains("复制")').hide();
                            $("#" + answer).html(mdHtml.render(v.answer));
                            lastpicurl = picarr[0];
                        } else if (pattern3.test(v.answer)) { //dall-e-3画图模型生成的单张图片，包含revised_prompt字段的结果
                            let picarr = (v.answer + "\n").replace(/\!\[IMG\]\(/g, "").split(")\n");
                            $("#" + answer).prev('div').find('div:contains("复制")').hide();
                            $("#" + answer).html(mdHtml.render(v.answer.split("\n")[0]));
                            lastpicurl = picarr[0];
                            revised_prompt = v.answer.split("dall-e-3官方修订提示词：")[1] + ".请基于以上描述画图，并按以下要求进行修改：";
                        } else {
                            mathjaxstr = (containsFormula(v.answer)) ? '<div class="home_chat-message-top-action" onclick="show_katex(this)">显示公式</div>' : '';
                            readmsg = (v.answer.startsWith("![IMG]")) ? '' : '<div class="home_chat-message-top-action" onclick="read_msg(this)">朗读文本</div>';
                            $("#" + answer).prev().html(mathjaxstr + $("#" + answer).prev().html() + readmsg);
                            temptext = v.answer;
                            contextarray.push([encodeURIComponent(v.question), encodeURIComponent(v.answer)]);
                            contextarray = contextarray.slice(-10);
                            if (temptext.split("\n").length == 1) {
                                temptext = temptext.replace(/\\n/g, '\n');
                            }
                            temptext = mdHtml.render(escapeMarkdownBackslashes(temptext));
                            $("#" + answer).html(temptext);
                            $("#" + answer + " pre code").each(function () {
                                $(this).html("<i style='float:right;cursor:pointer' class='layui-icon layui-icon-file copy_code'></i>" + $(this).html());
                            });
                        }
                        document.body.scrollTop = document.body.scrollHeight;
                    });
                    if ($("#question").val().length) {
                        user_output($("#question").val(), true);
                    }
                    chat_roll();
                    showpic();
                } else {
                    $.removeCookie('userrndstr', { path: '/' });
                    layer.alert('[展示对话]您已在其他地方扫码登录过，本地保存的登录信息已失效。', {
                        icon: 2,
                        btn: ['确定'],
                        closeBtn: 0,
                        yes: function () {
                            location.reload();
                        }
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Show Conversation Error:', error);
                setTimeout('showconversation(' + id + ');', 3000); // 增加重试间隔
            }
        });
    }

    //修改对话名称
    function changeConversationName() {
        if ($('#' + $.cookie('check_id') + ' .home_chat-item-title').text() == '新的聊天') {
            var one_msg = (($('#preview').length) ? "[图片]" : "");
            one_msg += (($('#blendpreview').length) ? "[图片]混合图片" : $('.home_chat-body code:first').text());
            $('#' + $.cookie('check_id') + ' .home_chat-item-title').text(one_msg);
            $('.window-header-main-title').text(one_msg);
        }
    }

    //分享按钮的下载功能
    function download_msg() {

        var downfile = new File(
            [$('.home_export-content').text()],
            $('.window-header-main-title').text() + ".md",
            { type: "text/plain" });

        var tmpLink = document.createElement("a");
        var objectUrl = URL.createObjectURL(downfile);

        tmpLink.href = objectUrl;
        tmpLink.download = downfile.name;
        document.body.appendChild(tmpLink);
        tmpLink.click();

        document.body.removeChild(tmpLink);
        URL.revokeObjectURL(objectUrl);
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

    function escapeMarkdownBackslashes(markdownText) {
        // 使用正则表达式匹配 "\["，"\]"，"\(" 和 "\)"，用于预处理公式
        return markdownText.replace(/\\([()[\]])/g, '\\\\$1');
    }
    // 返回消息格式化
    function renderhtml(strforcode) {
        $("#" + answer).html(mdHtml.render(escapeMarkdownBackslashes(strforcode)));
        if (reasoning_content !== "") {
            $("#" + answer).html('<div style="color:#8b8b8b;padding:0 0 0 10px;border-left:1px solid #8b8b8b;">' + mdHtml.render(reasoning_content) + '</div>' + $("#" + answer).html());
        }
        $("#" + answer + " pre code").each(function () {
            $(this).html("<i style='float:right;cursor:pointer' class='layui-icon layui-icon-file copy_code'></i>" + $(this).html());
        });

        if (!userScrolling) {
            chat_roll();
        }
    }

    function ismobilephone() {
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

    // 根据错误码返回信息
    function get_error_msg(errcode, errmessage) {
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
            case "billing_hard_limit_reached":
                errmsg = "API_KEY账户余额不足，请联系网站管理员";
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
            case "rate_limit_exceeded":
                errmsg = "超过了API_KEY的访问频率限制";
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
            case "mj_fail":
                if (errmessage == "") {
                    errmsg = "MJ图片生成失败，请确认您的提示词不违规并稍后再试";
                } else {
                    errmsg = errmessage;
                }
                break;
            case "DataInspectionFailed":
                errmsg = "问题中包含不适宜的内容，AI无法回答";
                break;
            case "audio_too_short":
                errmsg = "录音时间太短，无法识别";
                break;
            case "picture_not_allowed":
                errmsg = "上传的图片不合法，请换一张";
                break;
            default:
                errmsg = "错误类型：" + errcode + "<br>错误详情：" + htmlspecialchars(decodeURIComponent(errmessage));
        }
        return errmsg;
    }

    function showwxpayqrcode() {
        var productid = $('#productid').val();
        if (productid == "") {
            layer.msg("请选择充值卡类型");
            return false;
        }
        layer.close(buylayer);
        buylayer = 0;
        if (isWechat) {
            location.href = window.location.protocol + "//" + window.location.hostname + "/wxpay.php?m=1&p=" + productid + "&s=" + userrndstr;
        } else if (isMobile) {
            $("#mytitle").html("请用微信扫码支付");
            $("#mycontent").html("");
            $("#mycontent").qrcode(window.location.protocol + "//" + window.location.hostname + "/wxpay.php?p=" + productid + "&s=" + userrndstr);
            $("#mycontent").append("<p>您可以截图后在微信中识别二维码支付，或将下面的付款链接复制到微信中打开。</p><p style='word-break:break-all;' class='copy_p'>" + window.location.protocol + "//" + window.location.hostname + "/wxpay.php?p=" + productid + "&s=" + userrndstr + "</p>");

            layerqrcode = layer.open({
                type: 1,
                title: false,
                closeBtn: false,
                area: ['300px', '440px'],
                offset: ['calc(50% - 220px)', 'calc(50% - 150px)'],
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
        } else {
            $("#mycontent").html("");
            $("#mycontent").qrcode(window.location.protocol + "//" + window.location.hostname + "/wxpay.php?p=" + productid + "&s=" + userrndstr);

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

            if (isWechat) {
                $("#mytitle").html("使用支付宝付款");
                if (/iPhone/i.test(navigator.userAgent)) {
                    $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>点击下面的链接即可复制网址，在手机浏览器中访问即可跳转到支付宝进行支付。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;word-break:break-all;' onclick='location.href=\"" + alipayqrcodeurl + "\";'>" + alipayqrcodeurl + "</p>");
                } else {
                    $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>点击下面的链接即可复制网址，在手机浏览器中访问即可跳转到支付宝进行支付。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;word-break:break-all;' class='copy_p'>" + alipayqrcodeurl + "</p>");
                }
                layerqrcode = layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    area: ['300px', '350px'],
                    offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                    content: $('#mydialog'),
                    shadeClose: true,
                });
            } else if (isMobile) {
                $("#mytitle").html("使用支付宝付款");
                $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>点击下面的链接即可跳转到支付宝进行支付。</p><p style='font-size:18px;text-align:left;line-height:35px;color:#000080;word-break:break-all;' onclick='location.href=\"" + alipayqrcodeurl + "\";'>" + alipayqrcodeurl + "</p>");
                layerqrcode = layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    area: ['300px', '350px'],
                    offset: ['calc(50% - 175px)', 'calc(50% - 150px)'],
                    content: $('#mydialog'),
                    shadeClose: true,
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
            url: "checkwxpaystatus.php?userrndstr=" + userrndstr + "&begintime=" + currentTime + "&rid=" + randomString(10),
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
                closeBtn: 0,
                title: '确认删除',
                offset: '50%',
            }, function (index) {
                freezegogogo = false;
                layer.close(index);
                loading = 0;
                buttongo = true;
                $("#question").val("");
                $("#question").attr("disabled", false);
                $("#go").text(" 发送 ");
                if (!isMobile) $("#question").focus();
                layerconfirm = 0;
            }, function (index) {
                if ((!buttongo) && (loading != 0)) {
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
                    layerconfirm = 0;
                } else {
                    layerconfirm = 0;
                }
            });
        }
    }

    function showshare() {
        if (!user_uid) {
            layer.msg("请在登录后查看详情");
            return;
        }
        $("#mytitle").html("邀请有礼");
        $("#mycontent").html("<p style='font-size:18px;text-align:left;line-height:35px;'>您的好友使用以下链接访问本站并登录后，您和您的好友将各获得" + freetryshare + "次查询次数。</p><p id='shareurl' style='word-break:break-all;font-size:18px;text-align:left;line-height:35px;color:#000080;' class='copy_p'>" + window.location.protocol + "//" + window.location.hostname + "/index.php?i=" + user_uid + "</p><p onclick='downloadQRCode();' style='border:1px solid;width:160px;position:absolute;bottom:20px;left:calc(50% - 80px);font-size:18px;text-align:center;line-height:35px;'>点击下载二维码</p>");
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

    function togglefullscreen() {
        $('.home_container:first').toggleClass('home_tight-container');
        $('.max_min i').toggleClass('layui-icon-screen-restore');
        if ($.cookie('fullscreen')) {
            $.removeCookie('fullscreen');
        } else {
            $.cookie('fullscreen', 1);
        }
    }

    function browse_chat() {
        if ($("#quota").text() == "") {
            layer.msg("请登录后再查看");
            return;
        }
        layer.open({
            type: 1,
            title: '全部登录及对话日志',
            area: ['60%', '75%'],
            shade: 0.5,
            scrollbar: true,
            fixed: true, //设置为固定定位
            offset: '2.5%',
            content: '<iframe src="/ai/personal/show_log?userrndstr=' + userrndstr + "&rid=" + new Date().getTime() + '" style="width: 100%; height: 100%;"></iframe>'
        });
    }

    function downloadQRCode() {
        var tempurl = $("#shareurl").text();
        $("#mycontent").html("");
        $("#mycontent").qrcode(tempurl);
        if (isWechat) {
            var img = document.createElement("img");
            img.src = $("#mycontent").find("canvas")[0].toDataURL("image/png");
            $("#mycontent").html(img);
            layer.msg("长按二维码图片保存到本地");
        } else {
            var link = document.createElement("a");
            link.href = $("#mycontent").find("canvas")[0].toDataURL("image/png");
            link.download = "qrcode.png";
            link.click();
        }
    }

    function htmlspecialchars(str) {
        if (typeof str === 'string') {
            return str
                .replace(/&/g, '&amp;') // 将 & 转换为 &amp;
                .replace(/</g, '&lt;')   // 将 < 转换为 &lt;
                .replace(/>/g, '&gt;')   // 将 > 转换为 &gt;
                .replace(/"/g, '&quot;') // 将 " 转换为 &quot;
                .replace(/'/g, '&#039;'); // 将 ' 转换为 &#039; (或者 &apos;)
        } else {
            return str;
        }
    }

    function entersetting(state) {
        if (state) {
            $("#question").prop("placeholder", "Ctrl + Enter 发送");
            $("#question").off("keydown").keydown(function (e) {
                if (e.ctrlKey && e.which == 13) {
                    gogogo($('#question').val()); return false;
                }
            });
        } else {
            $("#question").prop("placeholder", "Enter 发送，Shift + Enter换行");
            $("#question").off("keydown").keydown(function (e) {
                if (!e.ctrlKey && !e.altKey && !e.shiftKey && e.which == 13) {
                    gogogo($('#question').val()); return false;
                }
            });
        }
    }

    function switchRecordingStatus(elem) {
        var $element = $(elem);
        var title = $element.attr("title");

        if (title === "发送语音") {
            $element.attr("title", "发送文字");
            $element.find("i").html("&#xe611;");
            $("#uploadpicbutton").hide();
            $("#textinputbutton").hide();
            $("#question").hide();
            $("#recordingbutton").show();
            isasrquestion = 1;
            if (!isrecordersuccess) {
                initRecording();
            }
        } else {
            $element.attr("title", "发送语音");
            $element.find("i").html("");
            $("#uploadpicbutton").show();
            $("#textinputbutton").show();
            $("#question").show();
            $("#recordingbutton").hide();
            isasrquestion = 0;
        }
    }

    function uploadimage() {
        // console.log($("#modelisimage").val());
        // console.log($("#modelvalue").val());
        // console.log($('#preview').length);
        // console.log($(".home_chat-item-selected .home_chat-item-title").text());
        if (($("#modelvalue").val() == "midjourney_image") && ($("#modelisimage").val() == 2) && ($(".home_chat-item-selected .home_chat-item-title").text() == "新的聊天") && ($('#preview').length)) {
            $("#uploadBlendImage").val("");
            $("#uploadBlendImage").click();
         } else {
             create_chat();
             $("#uploadImage").val("");
             $("#uploadImage").click();
         }
    }

    function previewImage(needblend = false) {
        if (needblend) {                    //上传第二张图片目前仅支持midjourney模型的blend动作
            var file = document.getElementById('uploadBlendImage').files[0];
            var previewimgname = 'blendpreview';
            disableModelContent('.modelisimage4');
        } else {
            var file = document.getElementById('uploadImage').files[0];
            var previewimgname = 'preview';
        }
        if (file && file.type.match('image.*')) {
            var reader = new FileReader();

            reader.onloadend = function () {
                var image = new Image();
                image.src = reader.result;
                image.onload = function () {
                    if (this.width > 0 && this.height > 0) {
                        if (!$('#' + previewimgname).length) {
                            var html = '<div class="home_chat-message-user">' +
                                '<div class="home_chat-message-container">' +
                                '<div class="home_chat-message-item">' +
                                '<div class="markdown-body"><img id="' + previewimgname + '" onload="chat_roll();showpic();">' +
                                '</div>' +
                                '</div>' +
                                '</div>' +
                                '</div>';
                            if (previewimgname == 'preview') {
                                if (welcomemessage) {
                                    $(".home_chat-message #welcomemessage").closest('.home_chat-message').after(html);
                                } else {
                                    $('.home_chat-body').prepend(html);
                                }
                            } else {
                                $(".home_chat-message-user img#preview").closest('.home_chat-message-user').after(html);
                            }
                        }
                        var preview = document.getElementById(previewimgname);
                        console.log(preview);
                        preview.src = reader.result;
                        preview.style.display = 'block';
                        disableModelContent('.modelisimage0');
                        disableModelContent('.modelisimage1');
                    }
                }
                image.onerror = function () {
                    console.log('Invalid image file.');
                }
            }
            if (file instanceof Blob) {
                reader.readAsDataURL(file);
            } else {
                console.error("参数不是有效的 Blob 或 File 类型");
            }
            //reader.readAsDataURL(file);
        } else {
            console.log('Not an image file.');
        }
    }

    function disableModelContent(cssClass = "") {
        $('.home_prompt-hint' + cssClass).css({
            'pointer-events': 'none',
            'color': 'var(--silver)'
        });
    }

    function enableModelContent(cssClass = "") {
        $('.home_prompt-hint' + cssClass).css({
            'pointer-events': 'auto',
            'color': 'var(--black)'
        });
    }

    function base64ToFile(data, filename) {
        var arr = data.split(','), mime = 'image/jpeg',
            bstr = atob(arr.length > 1 ? arr[1] : arr[0]), n = bstr.length, u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, { type: mime });
    }

    function previewResizeAndUpload(toPNG = false, needblend = false) {
        layer.msg("图片上传中……", { time: false });
        var reader = new FileReader();
        var file;
        if (needblend) {
            file = document.getElementById('uploadBlendImage').files[0];
        } else {
            file = document.getElementById('uploadImage').files[0];
        }
        reader.onloadend = function () {
            var image = new Image();
            image.onload = function () {
                var canvas = document.createElement('canvas');
                var context = canvas.getContext('2d');

                if (toPNG) {
                    var MAX_WIDTH = 1024;
                    var MAX_HEIGHT = 1024;
                } else {
                    var MAX_WIDTH = 1920;
                    var MAX_HEIGHT = 1920;
                }
                var width = image.width;
                var height = image.height;

                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                context.drawImage(image, 0, 0, width, height);

                if (toPNG) {
                    var dataUrl = canvas.toDataURL('image/png');
                    var base64 = dataUrl.split(',')[1];
                    file = base64ToFile(base64, file.name.replace(/\.[^/.]+$/, ".png"));
                } else {
                    var dataUrl = canvas.toDataURL('image/jpeg');
                    var base64 = dataUrl.split(',')[1];
                    file = base64ToFile(base64, file.name.replace(/\.[^/.]+$/, ".jpg"));
                }

                $.get("/ai/index/get_upload_token?userrndstr=" + userrndstr, function (data) {
                    var formData = new FormData();
                    formData.append('action', 'upload');
                    formData.append('nonce', data.nonce);
                    formData.append('sign', data.sign);
                    formData.append('image', file);
                    $.ajax({
                        url: imagesiteurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (data) {
                            console.log('upload successful!\n' + data);
                            if (needblend) {
                                $('#blendimageurl').val(data);
                                if (!(/^(http|https):\/\/[^ "]+$/.test(data))) {
                                    layer.msg("图片上传失败：" + data);
                                }
                            } else {
                                $('#imageurl').val(data);
                                if (/^(http|https):\/\/[^ "]+$/.test(data)) {
                                    starttoask();
                                } else {
                                    layer.msg("图片上传失败：" + data);
                                }
                            }
                        },
                        error: function (error) {
                            layer.msg("图片上传失败：" + error.responseText);
                        }
                    });
                }, "json").fail(function () {
                    layer.msg("图片上传失败，请联系站长确认图床是否正常");
                });

            };
            image.src = reader.result;
        };
        if (file instanceof Blob) {
            reader.readAsDataURL(file);
        } else {
            console.error("参数不是有效的 Blob 或 File 类型");
        }
    }

    function parseMarkdownImage(str, fortitle = false) {
        if (str.startsWith('![IMG]')) {
            var regex = /!\[IMG]\((.*?)\)/g;
            return str.replace(regex, fortitle ? '[图片]' : '<img src="$1">');
        } else {
            return str;
        }
    }

    function showwelcomemessage() {
        if (welcomemessage) {
            var html = '<div class="home_chat-message">' +
                '<div class="home_chat-message-container">' +
                '<div class="home_chat-message-item">' +
                '<div class="home_chat-message-top-actions">' +
                '</div>' +
                '<div class="markdown-body" id="welcomemessage"></div>' +
                '</div>' +
                '<div class="home_chat-message-actions">' +
                '<div class="home_chat-message-action-date"></div>' +
                '<div class="home_chat-message-action-date" style="margin-right:10px;"></div>' +
                '</div>' +
                '</div>' +
                '</div>';
            $('.home_chat-body').append(html);
            $("#welcomemessage").html(welcomemessage);
        }
    }

    function changeconversationtitle(id) {
        layer.prompt({
            title: '请输入自定义对话标题',
            formType: 2,
            value: $('#' + id + ' .home_chat-item-title').text(),
        }, function (value, index, elem) {
            var newtitle = value;
            if (newtitle.trim().length) {
                $('#' + id + ' .home_chat-item-title').text(newtitle);
                if (id == conversationid) {
                    $('.window-header-main-title').text(newtitle);
                }

                $.ajax({
                    url: "/ai/index/update_conversation_title?title=" + encodeURIComponent(newtitle) + "&conversationid=" + id + "&userrndstr=" + userrndstr,
                    dataType: "json",
                    error: function (xhr, status, error) {
                        layer.msg("网络错误或程序错误，请刷新重试。若一直无法成功，请联系管理员。");
                    }
                });
            }
            layer.close(index);
        });
    }
} catch (err) {
    console.log(err)
    //layer.msg(err);
}