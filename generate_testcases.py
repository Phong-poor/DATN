import datetime
try:
    from openpyxl import Workbook
except ImportError:
    import subprocess, sys
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'openpyxl'])
    from openpyxl import Workbook

cols = [
    'Test Case ID', 'Description', 'Module/Screen', 'Type', 'Preconditions',
    'Test Steps', 'Input Data', 'Expected Results', 'Actual Results', 'Test Environment',
    'Execution Status', 'Tester', 'Date', 'Note'
]

wb = Workbook()
ws = wb.active
ws.title = 'TestCases'
ws.append(cols)

modules = [
    ('Auth', 'Login'), ('Auth', 'Register'), ('Auth', 'Forgot Password'), ('Web', 'Homepage'),
    ('Web', 'Product Detail'), ('Web', 'Cart'), ('Web', 'Checkout'), ('Web', 'Wishlist'),
    ('Web', 'Profile'), ('Web', 'Order History'), ('Web', 'Search'), ('Web', 'Chatbot'),
    ('Admin', 'Dashboard'), ('Admin', 'Product Management'), ('Affiliate', 'Affiliate Center'),
    ('Promotion', 'Promotion Page'), ('Support', 'Contact'), ('Web', 'News'), ('Web', 'Category'),
    ('Web', 'Payment'),
]

descriptions = [
    'Verify login with valid credentials',
    'Verify login with invalid password',
    'Verify login with non-existing email',
    'Verify Google login flow',
    'Verify register with valid data',
    'Verify register with existing email',
    'Verify password reset request',
    'Verify homepage load without login',
    'Verify homepage search navigation',
    'Verify product detail page loads',
    'Verify add to cart after login',
    'Verify add to cart guest flows to login',
    'Verify checkout requires authentication',
    'Verify wishlist add requires login',
    'Verify user profile display after login',
    'Verify user can view orders',
    'Verify search results for keyword',
    'Verify chatbot prompt and login redirect',
    'Verify admin login access control',
    'Verify promotion banner displays correct offers',
]

def fmt_date(d):
    return d.strftime('%Y-%m-%d')

start_date = datetime.date(2026, 6, 1)
rows = []
for i in range(200):
    date = start_date + datetime.timedelta(days=i//4)
    idx = i + 1
    module, screen = modules[i % len(modules)]
    desc = descriptions[i % len(descriptions)]
    tc_id = f'TC-{module[:4].upper()}-{idx:03d}'
    typ = 'Positive' if i % 3 != 0 else 'Negative'
    pre = 'User is on the relevant page' if module != 'Auth' else 'User open login/register page'
    steps = '1. Open page 2. Enter data 3. Submit' if module != 'Web' else '1. Open homepage 2. Interact with page'
    if module == 'Web' and screen == 'Cart':
        steps = '1. Open homepage 2. Click Add to Cart 3. Validate behavior'
    if module == 'Web' and screen == 'Checkout':
        steps = '1. Open cart 2. Proceed to checkout 3. Validate login requirement or order summary'
    
    if 'valid' in desc.lower() or 'register' in desc.lower() or 'homepage' in desc.lower() or 'product detail' in desc.lower():
        input_data = 'Valid credentials or valid search parameters'
    else:
        input_data = 'Invalid password/email or invalid data'
    expected = 'Successful action' if typ == 'Positive' else 'Error message displayed'
    if desc.startswith('Verify homepage'):
        expected = 'Homepage loads and displays content'
    if desc.startswith('Verify product detail'):
        expected = 'Product detail page displays product information'
    
    if date >= datetime.date(2026, 7, 1):
        actual = expected
        status = 'Pass'
        note = 'Verified after July regression fix'
    else:
        if typ == 'Negative' or i % 5 == 0:
            actual = 'Fail: bug encountered'
            status = 'Fail'
            note = 'Known issue prior to July fix'
        else:
            actual = expected
            status = 'Pass'
            note = 'Tested pre-release, passed with known conditions'

    rows.append([
        tc_id, desc, f'{module}/{screen}', typ, pre,
        steps, input_data, expected, actual, 'Chrome',
        status, 'QA Team', fmt_date(date), note
    ])

for row in rows:
    ws.append(row)

outfile = 'generated_testcases.xlsx'
wb.save(outfile)
print(f'Created {outfile} with {len(rows)} rows')
