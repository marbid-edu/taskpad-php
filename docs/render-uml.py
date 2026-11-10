from pathlib import Path
from reportlab.pdfgen import canvas
from reportlab.lib.colors import HexColor, white
import math

out = Path(__file__).parent
INK=HexColor('#20382d'); GREEN=HexColor('#216749'); PALE=HexColor('#eaf1e5')
def start(name, subtitle):
    c=canvas.Canvas(str(out/name), pagesize=(842,595))
    c.setTitle('TaskPad - '+subtitle)
    c.setFillColor(INK);c.setFont('Helvetica-Bold',24);c.drawString(38,550,'TaskPad / '+subtitle)
    c.setFont('Helvetica',10);c.setFillColor(GREEN);c.drawString(38,530,'CIS 435  |  Mohamed Arbid  |  PHP application design')
    c.setStrokeColor(INK);c.setLineWidth(1)
    return c
def text(c,x,y,s,size=10,bold=False):
    c.setFillColor(INK);c.setFont('Helvetica-Bold' if bold else 'Helvetica',size);c.drawString(x,y,s)
def arrow(c,x1,y1,x2,y2,dash=False):
    c.setStrokeColor(INK);c.setDash(4,3) if dash else c.setDash()
    c.line(x1,y1,x2,y2);c.setDash()
    a=math.atan2(y2-y1,x2-x1)
    for delta in [-.45,.45]:c.line(x2,y2,x2-9*math.cos(a+delta),y2-9*math.sin(a+delta))
def footer(c,note):
    text(c,38,35,note,9);text(c,780,35,'1 / 1',9);c.save()

c=start('uml-usecase.pdf','Use case diagram')
c.setFillColor(white);c.roundRect(230,110,565,390,12,fill=1,stroke=1)
text(c,248,478,'TaskPad system',12,True)
# Actor
c.circle(100,333,15);c.line(100,318,100,268);c.line(70,299,130,299);c.line(100,268,78,240);c.line(100,268,122,240)
text(c,61,218,'TaskPad user',12,True)
def oval(cx,cy,label,w=185):
    c.setFillColor(PALE);c.ellipse(cx-w/2,cy-23,cx+w/2,cy+23,fill=1)
    c.setFillColor(INK);c.setFont('Helvetica',11);c.drawCentredString(cx,cy-4,label)
cases=[(380,432,'View tasks and counts'),(380,359,'Filter by text / priority'),(380,286,'Create task'),(380,213,'Complete task'),(380,140,'Delete task')]
for x,y,label in cases:
    c.line(130,299,x-92.5,y);oval(x,y,label)
oval(660,286,'Validate task input',200)
arrow(c,472.5,286,560,286,True);text(c,484,302,'<<include>>',9)
arrow(c,380,382,380,409,True);text(c,397,393,'<<extend>>',9)
text(c,550,440,'View includes overall counts:',10,True)
text(c,550,422,'total, in progress, completed.',10)
text(c,550,386,'Text and priority filters use AND.',10)
text(c,550,365,'Text matches title or description.',10)
text(c,550,203,'All mutations use protected POST.',10)
text(c,550,184,'Create always validates on the server.',10)
footer(c,'Associations connect the user to actions. Dashed arrows show UML include / extend relationships.')

c=start('uml-class.pdf','Class diagram')
def box(x,y,w,title,attrs,methods,stereo=None):
    title_h=43 if stereo else 29
    h=title_h+max(20,len(attrs)*15+12)+len(methods)*15+14
    c.setFillColor(white);c.rect(x,y-h,w,h,fill=1)
    c.setFillColor(PALE);c.rect(x,y-title_h,w,title_h,fill=1)
    c.setFillColor(INK);c.setFont('Helvetica-Bold',12);c.drawCentredString(x+w/2,y-20,title)
    if stereo:
        c.setFont('Helvetica',9);c.drawCentredString(x+w/2,y-34,stereo)
    yy=y-title_h-17
    for a in attrs:text(c,x+10,yy,a,9);yy-=15
    line=y-title_h-max(20,len(attrs)*15+12);c.line(x,line,x+w,line);yy=line-18
    for m in methods:text(c,x+10,yy,m,9);yy-=15
    return h
box(38,490,360,'Task',['+ id: string','+ title: string','+ description: string','+ priority: string','+ due: ?string','+ completed: bool','+ createdAt: string'],['+ create(input: array): Task {static}','+ toArray(): array','+ fromArray(row: array): Task {static}'])
box(490,490,314,'TaskRepository',['- path: string'],['+ all(): array','+ add(task: Task): void','+ complete(id: string): void','+ delete(id: string): void','- change(id: string, delete: bool): void','- access(update: ?callable): array'])
box(38,250,360,'public/index.php',[],['+ e(value: string): string','+ scalar(source: array, key: string, default: string): string','+ redirectToList(query: string, priority: string): never','+ hiddenContext(): void','+ fieldError(key: string): void','+ errorAttributes(key: string): string'],'<<page controller; procedural, not a PHP class>>')
box(490,250,314,'Validator',[],['+ validateCreate(input: array): array {static}'])
arrow(c,490,400,398,400,True);text(c,405,425,'hydrates /',8);text(c,405,413,'serializes',8)
arrow(c,218,250,218,285,True);text(c,232,266,'creates / displays',8)
arrow(c,398,225,490,340,True);text(c,401,314,'reads /',8);text(c,401,302,'mutates',8)
arrow(c,398,200,490,200,True);text(c,407,212,'validates',8)
# Model validates persisted records through the Validator too.
text(c,550,272,'Task also uses Validator for stored records.',8)
# Storage node
c.setFillColor(PALE);c.rect(535,89,227,55,fill=1);text(c,552,122,'<<file>> data/tasks.json',12,True);text(c,552,104,'JSON array of task records',10)
c.line(804,400,824,400);c.line(824,400,824,117);arrow(c,824,117,762,117);text(c,631,156,'locked read / write',9)
footer(c,'+ public  /  - private  /  Dashed arrows: dependency. Constructors omitted. PHP 8.4; no database server.')
print('Created 2 UML PDFs')

