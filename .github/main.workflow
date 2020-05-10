workflow "PHPCS Inspections" {
  resolves = ["Run phpcs inspection"]
  on = "push"
}

action "Run phpcs inspection" {
  uses = "rtCamp/rtCamp/action-phpcs-inspection@master"
  env = {
    DIFF_BASE="develop"
  }
}
