A simple script that chains together sox and [denoiser](https://pypi.org/project/denoiser/) to clean 
audio files, specifically denoise speech audio.

Install both (`apt-get install sox && pip install denoiser`). The current setup assumes
both clean.py and process.sh are in `/home/script/_tmp/dhamma`, and that folder contains a
folder 'mp3' and 'mp3-cleaned'.

You can edit `clean.py` to match your folder structure. 
