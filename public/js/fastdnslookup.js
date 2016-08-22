// 在新窗口打开链接，并且隐藏引用地址
function open_new_window(t) {
    if (t.innerHTML) {
        window.open("http://" + t.innerHTML, "_blank");
    }
}