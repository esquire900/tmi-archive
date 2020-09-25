import glob
import os

for f in glob.glob('./mp3/*.mp3'):
    f = f.replace('./mp3/', '')
    target_file = '/home/script/_tmp/dhamma/mp3-cleaned/{}'.format(f)
    if os.path.exists(target_file):
        continue
    try:
        os.system('bash process.sh /home/script/_tmp/dhamma/mp3/{} {}'.format(f, target_file))
    except Exception:
        print('failed {}'.format(f))
