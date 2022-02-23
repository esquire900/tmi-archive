import glob
import os

for f in sorted(glob.glob('/home/simon/Downloads/tmi-archive2/original/*.mp3'))[::-1]:
    f = f.split('/')[-1]
    target_file = '/home/simon/Downloads/tmi-archive2/clean/{}'.format(f)
    if os.path.exists(target_file):
        continue
    cmd = 'bash process.sh /home/simon/Downloads/tmi-archive2/original/{} {}'.format(f, target_file)
    print(cmd)
    try:
        os.system(cmd)
    except Exception:
        print('failed {}'.format(f))
