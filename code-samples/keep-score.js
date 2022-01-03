const KeepScoreElement = document.getElementById('keep-score')

let gamePlayerPrototype = { name: '', score: 0, added: '', total: 0, turn: false }

let gamePlayersDefault = [
		JSON.parse( JSON.stringify( gamePlayerPrototype ) ),
		JSON.parse( JSON.stringify( gamePlayerPrototype ) )
	]

const KeepScore = {
	
	data() {
		return {
			gamePlayers: this.getCurGame(),
			placeholder: 'Player Name',
			starting: true,
			playing: false,
			ending: false,
			loadedFromSave: false
		}
	},
	
	methods: {
		
		addPlayer() {
			
			this.gamePlayers.push( JSON.parse( JSON.stringify( gamePlayerPrototype ) ) )
		
		},
		
		startGame() {

			let canStart = true

			// don't start if there are any un-named players
			for ( gamePlayer of this.gamePlayers ) {
				if ( gamePlayer.name == '' ) canStart = false
			}

			if ( canStart ) {
			
				this.starting = false
				this.playing = true
				this.ending = false
				
				KeepScoreElement.className = ''
				KeepScoreElement.classList.add('playing')

				let listElements = document.getElementsByTagName('li')

				let listElement = listElements[0]

				listElement.classList.add('turn')

				this.pullFocus( listElement )

				this.saveCurGame()

			} else {

				alert('Please give all players a name first.')

			}
		
		},

		pullFocus( element ) {

			for ( const child of element.children ) {

				if ( child.className == 'add' ) {

					child.children[0].focus()

				} 

			}

		},
		
		playerTurn( event ) {

			let listElement = event.target.closest('li')

			if ( listElement.className != 'turn' && this.playing ) {

				let listElement = event.target.closest('li')

				for ( const listItem of document.getElementsByTagName('li') ) {
					listItem.classList.remove('turn')
				}

				listElement.classList.add('turn')

				this.pullFocus( listElement )

				this.addScores()

				this.saveCurGame()

			}

		},

		addScores() {

			// add scores that need to be added
			for ( gamePlayer of this.gamePlayers ) {
				
				if ( gamePlayer.total != 0 ) {
					
					gamePlayer.score = gamePlayer.total
					gamePlayer.total = 0
					gamePlayer.added = ''
				
				}
			
			}

		},
		
		endGame( event ) {

			this.starting = false
			this.playing = false
			this.ending = true

			this.addScores()

			this.gamePlayers.sort( (a, b) => b.score - a.score )

			KeepScoreElement.className = ''
			KeepScoreElement.classList.add('ending')

			let listElement = event.target.closest('li')

			for ( const listItem of document.getElementsByTagName('li') ) {
				listItem.classList.remove('turn')
			}

			localStorage.clear()

		},
		
		addScore( event ) {

			let playerId = event.target.nextSibling.value

			this.gamePlayers[playerId].total = Number(this.gamePlayers[playerId].score) + Number(this.gamePlayers[playerId].added)

		},

		saveCurGame() {

			localStorage.setItem( 'curGame', JSON.stringify( this.gamePlayers ) )

		},

		getCurGame() {

			let curGame = localStorage.getItem( 'curGame' )

			if ( !curGame || curGame == 'undefined' ) {

				curGame = gamePlayersDefault

				this.loadedFromSave = false

			} else {

				curGame = JSON.parse( curGame )

				this.saveCurGame()

				this.loadedFromSave = true

			}

			return curGame

		}
	
	},

}

Vue.createApp(KeepScore).mount('#keep-score')
