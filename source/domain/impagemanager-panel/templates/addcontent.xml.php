<vertgroup>
  <children>
    <horizgroup>
      <args>
        <width>0%</width>
      </args>
      <children>
    
        <label>
          <args>
            <label><?=$contentTypeLabel?></label>
          </args>
        </label>
    
        <combobox>
          <args>
            <id>pagetype</id>
            <elements type="array"><?=$pagesComboList?></elements>
          </args>
        </combobox>

        <button>
          <args>
            <horiz>true</horiz>
            <frame>false</frame>
            <themeimage>mathadd</themeimage>
            <label><?=$addContentLabel?></label>
            <action>javascript:void(0)</action>
          </args>
          <events>
            <click>
              var page = document.getElementById('pagetype');
              var pagevalue = page.options[page.selectedIndex].value;
              var elements = pagevalue.split('/');
              xajax_AddContent(elements[0], elements[1], <?=$parentId?>)
            </click>
          </events>
        </button>

      </children>
    </horizgroup>
  
    <horizbar/>
  
    <divframe>
      <name>pageeditor</name>
      <args>
        <id>pageeditor</id>
      </args>
      <children>
        <void/>
      </children>
    </divframe>
  
    <impagemanager />

  </children>
</vertgroup>
