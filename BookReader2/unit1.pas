unit Unit1; 

{$mode objfpc}{$H+}

interface

uses
  Classes, SysUtils, FileUtil, LResources, Forms, Controls, Graphics, Dialogs,
  StdCtrls, Menus, ActnList, EditBtn, ComCtrls, Buttons;

type

  { TForm1 }

  TForm1 = class(TForm)
    aAdd: TAction;
    aDelete: TAction;
    aGo: TAction;
    aExit: TAction;
    ActionList1: TActionList;
    BitBtn1: TBitBtn;
    BitBtn2: TBitBtn;
    BitBtn3: TBitBtn;
    ComboBox1: TComboBox;
    DirectoryEdit1: TDirectoryEdit;
    ImageList1: TImageList;
    ListBox1: TListBox;
    MainMenu1: TMainMenu;
    miFile: TMenuItem;
    miExit: TMenuItem;
    ToolBar1: TToolBar;
    tbAdd: TToolButton;
    ToolButton2: TToolButton;
    ToolButton3: TToolButton;
    ToolButton4: TToolButton;
    procedure miExitClick(Sender: TObject);
  private
    { private declarations }
  public
    { public declarations }
  end; 

var
  Form1: TForm1; 

implementation

{ TForm1 }

procedure TForm1.miExitClick(Sender: TObject);
begin
  Close;
end;

initialization
  {$I unit1.lrs}

end.

