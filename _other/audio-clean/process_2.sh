#!/bin/bash
rm -rf /tmp/audio-process
mkdir /tmp/audio-process
cd /tmp/audio-process

cp "$1"original.mp3 /tmp/audio-process/original.mp3
cd /tmp/audio-process
sox original.mp3 -r 16k wav.wav
sox wav.wav left.wav remix 1
sox wav.wav right.wav remix 2
mkdir cuts
sox right.wav cuts/right.wav trim 0 60 : newfile : restart
sox left.wav cuts/left.wav trim 0 60 : newfile : restart

python /home/script/_tmp/FullSubNet/recipes/dns_interspeech_2020/inference.py -C /home/script/_tmp/FullSubNet/recipes/dns_interspeech_2020/fullsubnet/inference.toml -M /home/script/_tmp/FullSubNet/recipes/dns_interspeech_2020/net.tar -O /tmp/audio-process/cuts_out/

cd cuts_out/enhanced_0058

sox left* left.wav rate -L -s 44100
sox right* right.wav rate -L -s 44100
sox -M left.wav right.wav out.mp3

# you can do this here, but we're going to batch it over all known files
#/home/simon/Downloads/loudgain.static -I3 -S -L -a -k -s e out.mp3
cp out.mp3 "$1"cleaned_fullsubnet.mp3

rm -rf /tmp/audio-process
