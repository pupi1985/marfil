var myCounter = new Countdown({
    onUpdateStatus: function (sec) {
        document.getElementById("secondsDisplay").textContent = sec;
    },
    onCounterEnd: function () {
        window.location.href = window.location.href;
    }
});

function autorefreshChange() {
    var autorefreshEnabled = document.getElementById("autorefreshCheckbox").checked;
    sessionStorage.setItem("autorefreshEnabled", autorefreshEnabled);

    var seconds = Number(document.getElementById("secondsInput").value);
    sessionStorage.setItem("autorefreshSeconds", seconds);

    if (autorefreshEnabled) {
        if (seconds >= 60) {
            myCounter.start(seconds);
        }
    } else {
        myCounter.stop();
    }
};

window.onload = function () {
    if (sessionStorage.getItem("autorefreshEnabled") === "true") {
        var seconds = Number(sessionStorage.getItem("autorefreshSeconds"));

        document.getElementById("autorefreshCheckbox").checked = true;
        document.getElementById("secondsInput").value = seconds;

        myCounter.start(seconds);
    }
};

function Countdown(options) {
    var timer,
        instance = this,
        seconds = options.seconds || 10,
        updateStatus = options.onUpdateStatus || function () { },
        counterEnd = options.onCounterEnd || function () { };

    function decrementCounter() {
        updateStatus(seconds);
        if (seconds === 0) {
            counterEnd();
            instance.stop();
        }
        seconds--;
    }

    this.start = function (newSeconds) {
        clearInterval(timer);
        timer = 0;
        if (typeof newSeconds === "undefined") {
            seconds = options.seconds;
        } else {
            seconds = newSeconds;
        }
        timer = setInterval(decrementCounter, 1000);
        updateStatus(seconds);
        decrementCounter();
    };

    this.stop = function () {
        clearInterval(timer);
    };
}

function show_confirm() {
    return confirm("Are you sure you want to perform this operation?");
}
