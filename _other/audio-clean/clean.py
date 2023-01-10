import glob
import os

audio_folder = '/data1/projects/tmi_archive/rsync-server'
original_files = sorted(glob.glob(f'{audio_folder}/*/original.mp3'))
for original_file in original_files:
    talk_id = original_file.split('/')[-2]
    target_file = f'{audio_folder}/{talk_id}/cleaned_fullsubnet.mp3'
    if os.path.exists(target_file):
        continue
    cmd = f'bash process_2.sh {audio_folder}/{talk_id}/'
    try:
        os.system(cmd)
    except Exception:
        print('failed {}'.format(f))
