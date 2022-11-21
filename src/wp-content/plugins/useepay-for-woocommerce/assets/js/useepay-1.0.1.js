/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

(function (p) {
    p.createAndSubmitFormFor3ds = function (threeDSMethodCompletionUrl, actionUrl, params, method) {
        var IFRAME_NAME = 'threeDSMethodIframe';
        var threedsContainer = document.getElementsByTagName('body')[0];
        var _iframe = window.ThreedDS2Utils.createIframe(threedsContainer, IFRAME_NAME, '0', '0');
        var _form = document.createElement("form");
        threedsContainer.appendChild(_form);
        _form.action = actionUrl;
        _form.method = method;
        _form.target = IFRAME_NAME;
        if (typeof params === 'object') {
            for (var key in params) {
                var input = document.createElement("input");
                input.name = key;
                input.value = params[key];
                input.type = 'hidden';
                _form.appendChild(input);
            }
        }
        setTimeout( function () {
            threedsContainer.removeChild(_form);
        }, 1000 );
        _form.submit();

        setTimeout(function () {
            const formTimeOut =  window.ThreedDS2Utils.createForm('threedsMethodForm', threeDSMethodCompletionUrl, IFRAME_NAME, '0', '0');
            threedsContainer.appendChild(formTimeOut);
            formTimeOut.submit();
        }, 10000);

        window.addEventListener("message", function (e)
        {
            var data = e.data;
            data = JSON.parse(data);
            console.log(data);
            if (data && data.resp) {
                let json;
                try{
                    json = JSON.parse(data.resp);
                } catch (e) {
                    p.pollOrderStatusAndRedirect(data.queryOrderStatusUrl, data.queryOrderRedirectUrl, data.lastOrderStatus);
                    return;
                }
                if (json.resultCode === 'challenge') {
                    window.location.href = json.redirectUrl;
                } else {
                    let url = new URL(data.returnUrl);
                    url.searchParams.append('resp', data.resp);
                    window.location.href = url.toString();
                }
            } else {
                p.pollOrderStatusAndRedirect(data.queryOrderStatusUrl, data.queryOrderRedirectUrl, data.lastOrderStatus);
            }
        });
    };
    p.showPageLoading = function (loading_image, loading_text) {
        if ($('.useepay-page-loading').length === 0) {
            $('body').append('<div class="useepay-page-loading"><img src="' + loading_image + '">' + '<h1>' + loading_text + '</h1></div>');
        }
    };
    p._poll = function () {
        $.ajax(
            {
                type: 'GET',
                url: p.pollUrl,
                success: function (status) {
                    if ((status !== -1 && status !== p.pollLastOrderStatus) || p.time > 20) {
                        window.location.href = p.pollRedirectUrl;
                        return;
                    }
                    p.time++;
                    setTimeout(p._poll, 1000);
                },
                error: function (error) {
                    if (p.time > 20) {
                        window.location.href = p.pollRedirectUrl;
                        return;
                    }
                    p.time++;
                    setTimeout(p._poll, 1000);
                },
            }
        );
    }
    p.pollOrderStatusAndRedirect = function (url, redirectUrl, lastOrderStatus) {
        p.time = 1;
        p.pollUrl = url;
        p.pollRedirectUrl = redirectUrl;
        p.pollLastOrderStatus = lastOrderStatus;
        p._poll(url, redirectUrl);
    };
    p.query3dsNextStepAndRedirect = function (threedsNextStepQueryUrl, returnUrl, queryOrderStatusUrl, queryOrderRedirectUrl, lastOrderStatus) {
        var respJson = {'queryOrderStatusUrl':queryOrderStatusUrl, 'queryOrderRedirectUrl':queryOrderRedirectUrl, 'lastOrderStatus':lastOrderStatus, 'returnUrl':returnUrl};
        console.log('respJson:', respJson);
        $.ajax(
            {
                type: 'GET',
                url: threedsNextStepQueryUrl,
                success: function (resp) {
                    if (resp === -1) {
                        window.parent.postMessage(JSON.stringify(respJson));
                    } else {
                        respJson.resp = resp;
                        window.parent.postMessage(JSON.stringify(respJson));
                    }
                },
                error: function (error) {
                    window.parent.postMessage(JSON.stringify(respJson));
                }
            }
        );
    }


})(UseePay = {});
