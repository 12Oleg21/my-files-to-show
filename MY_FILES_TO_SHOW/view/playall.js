var current = 0;
var audio;

function Playfile(filepath, index)
{
    audio = $('#audio')[0];
    playlist = $('#playlist');
    buttons  = playlist.find('li button');
    btn = buttons[index];
    current = index;
    run(filepath, audio, $(btn), buttons);
}

function init() {
    audio = $('#audio')[0];
    if(audio){
        playlist = $('#playlist');
        tracks = playlist.find('li button');
        len = tracks.length - 1;
        audio.volume = .50;
        buttons  = playlist.find('li button');
        if(current >= len){
            current = 0;
            btn = buttons[0];
            link = $(btn).data("src");
        } else {
            current++;
            btn = buttons[current];
            link = $(btn).data("src");
        }
        run(link, audio, $(btn), buttons);
    }else{
        console.log('There are not any audio tags');
    }
}

function run(link, player, btn, buttons) {
    buttons.removeClass('active');
    player.src = link;
    audio.load();
    btn.addClass('active');
    audio.play();
}

