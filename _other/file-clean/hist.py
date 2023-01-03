# clean bunch of imported files
df = pd.read_sql('select id, title, original_file_name, recorded_date from talks_talk', conn)

# clean 1
df_fname = df[~pd.isna(df.original_file_name)]
start_with_date = df_fname.original_file_name.apply(lambda x: str(x[:6]).isdigit())
df_fname = df_fname[start_with_date]

from dateutil.parser import parse
import datetime

for tup in df_fname.iloc[:].itertuples():

    try:
        dstr = str(tup.original_file_name)[:6]
        d = datetime.date(int(f'20{dstr[:2]}'), int(dstr[2:4]), int(dstr[-2:]))
        sql = f'update talks_talk set recorded_date = \'{d}\' where id={tup.id};'
        conn.execute(sql)
        title = tup.title.replace('Imported - ', '').replace(dstr, '').replace('  ', ' ').strip()
        sql = f'update talks_talk set title = \'{title}\' where id={tup.id};'
        conn.execute(sql)

    except ValueError:
        continue


# batch 2
dfi = df[df.title.str.contains('Imported')]
for tup in dfi.iloc[:].itertuples():
    try:
        date = parse(tup.title, fuzzy=True).date()
        if date.year > 2000 and date.year <= 2019:
            sql = f'update talks_talk set recorded_date = \'{date}\' where id={tup.id};'
            conn.execute(sql)
            title = ' '.join(parse(tup.title, fuzzy_with_tokens=True)[1])
            title = title.replace('Imported - ', '').replace(dstr, '').replace('  ', ' ').strip()
            sql = f'update talks_talk set title = \'{title}\' where id={tup.id};'
            conn.execute(sql)
    except ValueError:
        continue
