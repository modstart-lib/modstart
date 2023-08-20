const Ui = {
    onResize(ele, cb) {
        if (!ele || !window.ResizeObserver) {
            return;
        }
        var doc = ele.ownerDocument;
        var win = doc.defaultView;
        var resizeTimer = null;
        // 如果不取 win.ResizeObserver 在父页面监听iframe的元素会抛异常
        // ResizeObserver loop completed with undelivered notifications
        var resizeObserver = new win.ResizeObserver(function (entries) {
            if (resizeTimer) {
                clearTimeout(resizeTimer);
            }
            resizeTimer = setTimeout(function () {
                cb();
            }, 1000);
        });
        resizeObserver.observe(ele);
    },
    state: {
        events: {
            change: [],
        },
        change: function (cb) {
            this.events.change.push(cb);
        },
        push: function (url, data) {
            data = data || {};
            data._url = url;
            window.history.pushState(data, null, url);
        },
        pushChange: function (url, data) {
            data = data || {};
            data._url = url;
            this.push(url, data);
            this.events.change.forEach(function (cb) {
                cb(data);
            });
        },
        init: function (initData) {
            initData = initData || {};
            initData._url = window.location.href;
            window.addEventListener('popstate', function (e) {
                MS.ui.state.events.change.forEach(function (cb) {
                    cb(e.state || initData);
                });
            });
        }
    },
    size: function () {
        // @width-sm-max: 40rem; // 0px    - 800px
        // @width-md: 40rem; // 800px - 1199px
        // @width-lg: 60rem; // 1200px - 1799px
        // @width-xl: 90rem; // 1800px -
        var w = window.innerWidth;
        if (w >= 1800) {
            return 'xl';
        } else if (w >= 1200) {
            return 'lg';
        } else if (w >= 800) {
            return 'md';
        }
        return 'sm';
    },
    /**
     * 是否为指定大小的屏幕
     * @param sizes array ['sm','md','lg','xl']
     * @param cb function
     */
    sizeCall: function (sizes, cb) {
        if (sizes === 'all') {
            sizes = ['sm', 'md', 'lg', 'xl'];
        }
        var size = MS.ui.size();
        if (sizes.indexOf(size) >= 0) {
            cb(size);
        }
    }
}

module.exports = Ui;
