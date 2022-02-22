import glob
import os

for f in glob.glob('/home/simon/Downloads/tmi-archive2/original/*.mp3'):
    f = f.split('/')[-1]
    target_file = '/home/simon/Downloads/tmi-archive2/clean/{}'.format(f)
    if os.path.exists(target_file):
        continue
    try:
        os.system('bash process.sh /home/simon/Downloads/tmi-archive2/original/{} {}'.format(f, target_file))
    except Exception:
        print('failed {}'.format(f))
