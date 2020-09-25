#!/bin/bash
rm -rf tmp-audio
mkdir tmp-audio
cd tmp-audio
sox $1 cut-files.wav trim 0 60 : newfile : restart
mkdir normalized
for file in ./*.wav; do
  sox "$file" normalized/"$file" --norm rate -L -s 16000 remix 1,2
done
cd normalized
python -m denoiser.enhance --noisy_dir=./ --out_dir=./denoised --device cuda --dns64
cd denoised
sox --norm *_enhanced.wav $2 rate -L -s 44100
cd ../../../
rm -r tmp-audio
