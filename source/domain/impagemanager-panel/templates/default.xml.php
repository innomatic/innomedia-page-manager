<vertgroup>
  <children>
    <horizgroup>
          <args>
            <width>0%</width>
          </args>
      <children>

        <!-- Content tree -->

        <vertgroup>
          <args>
            <width>200</width>
          </args>
          <children>
            <horizgroup>
              <args>
                <width>0%</width>
                <align>middle</align>
              </args>
              <children>

                <image>
                  <args>
                    <width>18</width>
                    <height>18</height>
                    <imageurl><?=$homeImageUrl?></imageurl>
                    <link><?=$homeAction?></link>
                  </args>
                </image>
                <link>
                  <args>
                    <label><?=$homeLabel?></label>
                    <bold>true</bold>
                    <link><?=$homeAction?></link>
                  </args>
                </link>

              </children>
            </horizgroup>
          
<?php if (isset($treeMenu)): ?>
            <treevmenu><args><menu><?=$treeMenu;?></menu></args></treevmenu>
<?php endif; ?>
          </children>
        </vertgroup>

        <vertbar />

        <vertgroup>
      <args>
        <width>100%</width>
      </args>
          <children>

            <!-- Page details -->

            <label>
              <args>
                <label><?=$pageNameString?></label>
                <bold>true</bold>
              </args>
            </label>
            
<?php if ($isModule == 0): ?>
            <horizgroup>
              <args>
                <width>0%</width>
              </args>
              <children>

<?php if ($isModule == 0): ?>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>pencil</themeimage>
                  <label><?=$editContentLabel?></label>
                  <action><?=$editAction?></action>
                </args>
              </button>
<?php endif; ?>

<?php if ($isContentPage == 1): ?>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>trash</themeimage>
                  <dangeraction>true</dangeraction>
                  <label><?=$deleteItemLabel?></label>
                  <needconfirm>true</needconfirm>
                  <confirmmessage><?=$deleteConfirmMessage?></confirmmessage>
                  <action><?=$deleteAction?></action>
                </args>
              </button>

<?php endif; ?>

              </children>
            </horizgroup>

<?php if ($isStaticPage == 0 or $isHomePage == 1): ?>
            <horizbar />
<?php endif; ?>
<?php endif; ?>

<?php if ($isStaticPage == 0 or $isHomePage == 1): ?>

            <!-- Page children -->

            <label>
              <args>
                <label><?=$childrenContentLabel?></label>
                <bold>true</bold>
              </args>
            </label>

<?php if ($isContentPage == 1): ?>
            <horizgroup>
              <children>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>mathadd</themeimage>
                  <label><?=$newContentLabel?></label>
                  <action><?=$addAction?></action>
                </args>
              </button>

              </children>
            </horizgroup>
<?php endif; ?>
<?php if ($childrenCount == 0): ?>
            <label>
              <args>
                <label><?=$noChildrenLabel?></label>
              </args>
            </label>
<?php else: ?>
                <table>
                  <args>
                    <headers type="array"><?=$tableHeaders?></headers>
                    <width>100%</width>
                  </args>
                  <children>

<?php $tableRow = 0; ?>
<?php foreach ($pageChildren as $page): ?>

                    <link row="<?=$tableRow?>" col="0">
                      <args>
                        <label><?=\Shared\Wui\WuiXml::cdata($page['name'])?></label>
                        <link><?=\Shared\Wui\WuiXml::cdata($page['viewaction'])?></link>
                      </args>
                    </link>
                    <label row="<?=$tableRow?>" col="1">
                      <args>
                        <label><?=\Shared\Wui\WuiXml::cdata($page['type'])?></label>
                      </args>
                    </label>

<?php if ($isContentPage == 1 or $isHomePage == 1): ?>

    <innomatictoolbar row="<?=$tableRow?>" col="2">
      <args>
        <frame>false</frame>
        <toolbars type="array"><?=\Shared\Wui\WuiXml::encode($page['toolbars'])?>'</toolbars>
      </args>
    </innomatictoolbar>

<?php endif; ?>
<?php $tableRow++; ?>

<?php endforeach; ?> 

                  </children>
                </table>

<?php endif; ?>
<?php endif; ?>

          </children>
        </vertgroup>

      </children>
    </horizgroup>
  </children>

</vertgroup>